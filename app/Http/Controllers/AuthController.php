<?php

namespace App\Http\Controllers;

use App\Models\PendingRegistration;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use App\Notifications\PendingRegistrationVerifyEmail;
use App\Services\SmsService;
use App\Services\TurnstileService;
use App\Support\IdentityNormalizer;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showRegister(TurnstileService $turnstile)
    {
        return view('auth.register', [
            'turnstileEnabled' => $turnstile->isEnabled(),
            'turnstileSiteKey' => $turnstile->siteKey(),
        ]);
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function showResetPassword(string $token, Request $request)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', old('email')),
        ]);
    }

    public function register(Request $request, TurnstileService $turnstile, SmsService $sms)
    {
        $registrationKey = 'registration|'.$request->ip();
        abort_if(RateLimiter::tooManyAttempts($registrationKey, 5), 429, 'Too many registration attempts. Please try again later.');
        RateLimiter::hit($registrationKey, 900);

        if (! $turnstile->verify($request->input('cf-turnstile-response'), $request->ip())) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Please complete the security verification check and try again.',
            ]);
        }

        $normalizedName = IdentityNormalizer::normalizeName($request->string('name')->toString());
        $normalizedEmail = IdentityNormalizer::normalizeEmail($request->string('email')->toString());
        $normalizedPhone = IdentityNormalizer::normalizePhone($request->string('phone')->toString());

        $request->merge([
            'name' => $normalizedName,
            'email' => $normalizedEmail,
            'phone' => $normalizedPhone ?? '',
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'date_of_birth' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'min:7', 'max:30'],
            'password' => $this->strongPasswordRules(),
            'terms' => ['accepted'],
        ]);

        $canonicalEmail = IdentityNormalizer::canonicalEmail($validated['email']);
        $dob = date('Y-m-d', strtotime($validated['date_of_birth']));

        // 1. Check direct uniqueness in verified users (canonical email + normalized phone)
        if (User::where('email', $validated['email'])->orWhere('canonical_email', $canonicalEmail)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'An account with this email address already exists.',
            ]);
        }

        if (User::where('phone', $validated['phone'])->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'An account with this mobile number already exists.',
            ]);
        }

        // 2. Duplicate patient identity check: (LOWER(name), date_of_birth) or (LOWER(name), normalized_phone)
        $duplicateIdentityExists = User::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($validated['name'])])
            ->where(function ($query) use ($dob, $validated): void {
                $query->whereDate('date_of_birth', $dob)
                    ->orWhere('phone', $validated['phone']);
            })
            ->exists();

        if ($duplicateIdentityExists) {
            throw ValidationException::withMessages([
                'duplicate_account' => 'An account matching your name and date of birth or mobile number may already exist. Please sign in or reset your password instead of creating another account.',
            ]);
        }

        // 3. Purge expired pending signups and ensure no conflicting active pending registration holds this phone/email
        PendingRegistration::where('expires_at', '<', now())->delete();

        $conflictingPhonePending = PendingRegistration::where('phone', $validated['phone'])
            ->where('canonical_email', '!=', $canonicalEmail)
            ->exists();

        if ($conflictingPhonePending) {
            throw ValidationException::withMessages([
                'phone' => 'A verification is already in progress for this mobile number. Please wait for it to finish or expire.',
            ]);
        }

        $conflictingEmailPending = PendingRegistration::where('canonical_email', $canonicalEmail)
            ->where('phone', '!=', $validated['phone'])
            ->exists();

        if ($conflictingEmailPending) {
            throw ValidationException::withMessages([
                'email' => 'A verification is already in progress for this email address. Please wait for it to finish or expire.',
            ]);
        }

        $plainEmailToken = Str::random(40);
        $smsOtpEnabled = (bool) config('services.sms.otp_enabled', false);
        $otp = $smsOtpEnabled ? (string) random_int(100000, 999999) : null;
        $now = now();

        $pending = PendingRegistration::updateOrCreate(
            [
                'canonical_email' => $canonicalEmail,
                'phone' => $validated['phone'],
            ],
            [
                'token' => Str::random(64),
                'name' => $validated['name'],
                'email' => $validated['email'],
                'date_of_birth' => $dob,
                'password' => Hash::make($validated['password']),
                'email_token_hash' => hash('sha256', $plainEmailToken),
                'email_verified_at' => null,
                'phone_otp_hash' => $otp !== null ? Hash::make($otp) : '',
                'phone_otp_expires_at' => $smsOtpEnabled ? $now->copy()->addMinutes(10) : $now,
                'phone_otp_attempts' => 0,
                'phone_verified_at' => null,
                'last_sms_sent_at' => $smsOtpEnabled ? $now : null,
                'last_email_sent_at' => $now,
                'expires_at' => $now->copy()->addMinutes(60),
            ]
        );

        $request->session()->put('pending_registration_token', $pending->token);

        $verificationUrl = $this->pendingEmailVerificationUrl($pending, $plainEmailToken);

        $mailSent = $this->attemptMail(
            fn () => Notification::route('mail', $pending->email)
                ->notify(new PendingRegistrationVerifyEmail($pending->name, $verificationUrl, 60)),
            'Failed to send pending registration verification email.',
            ['pending_registration_id' => $pending->id],
            warning: true,
        );

        $smsSent = false;
        if ($smsOtpEnabled && $otp !== null) {
            $smsSent = $this->attemptSms(
                fn () => $sms->sendOtp($pending->phone, $otp, 10),
                'Failed to send pending registration SMS OTP.',
                ['pending_registration_id' => $pending->id],
            );
        }

        if ($smsOtpEnabled) {
            $message = match (true) {
                $mailSent && $smsSent => 'Verification steps sent! Please verify both your email link and the 6-digit SMS code sent to your mobile number.',
                ! $mailSent && $smsSent => 'SMS verification code sent, but we could not send the verification email yet. Try resending the email below.',
                $mailSent && ! $smsSent => 'Verification email sent, but we could not send the SMS code right now. Try resending the SMS code below.',
                default => 'Pending registration saved, but we could not send your verification email or SMS code yet. Try resending below.',
            };
            $allSent = $mailSent && $smsSent;
        } else {
            $message = $mailSent
                ? 'Verification link sent! Please check your email and click the verification link to activate your account.'
                : 'Pending registration saved, but we could not send the verification email yet. Try resending below.';
            $allSent = $mailSent;
        }

        $response = [
            'message' => $message,
            'pending_token' => $pending->token,
            'mail_sent' => $mailSent,
            'sms_sent' => $smsSent,
            'sms_otp_enabled' => $smsOtpEnabled,
            'redirect' => route('registration.verify.notice', [], false),
        ];

        return $request->expectsJson()
            ? response()->json($response, 202)
            : redirect()->route('registration.verify.notice')
                ->with($allSent ? 'success' : 'warning', $message);
    }

    public function showPendingVerification(Request $request)
    {
        $pending = $this->resolvePendingRegistration($request);

        if (! $pending) {
            return redirect()->route('register')
                ->withErrors(['email' => 'Your registration session expired or was not found. Please start again.']);
        }

        return view('auth.verify-registration', [
            'pending' => $pending,
            'maskedEmail' => $this->maskEmail($pending->email),
            'maskedPhone' => $this->maskPhone($pending->phone),
            'smsOtpEnabled' => (bool) config('services.sms.otp_enabled', false),
            'smsCooldown' => $this->cooldownRemaining($pending->last_sms_sent_at),
            'emailCooldown' => $this->cooldownRemaining($pending->last_email_sent_at),
        ]);
    }

    public function verifyPendingEmail(Request $request, string $token, string $emailToken)
    {
        $pending = PendingRegistration::where('token', $token)->first();

        if (! $pending || $pending->isExpired()) {
            if ($pending) {
                $pending->delete();
            }
            abort(403, 'This registration verification link has expired.');
        }

        if (! hash_equals($pending->email_token_hash, hash('sha256', $emailToken))) {
            abort(403, 'Invalid verification link.');
        }

        if (! $pending->hasVerifiedEmail()) {
            $pending->forceFill(['email_verified_at' => now()])->save();
        }

        $request->session()->put('pending_registration_token', $pending->token);

        if ($pending->isFullyVerified()) {
            return $this->finalizePendingRegistration($pending, $request);
        }

        return redirect()->route('registration.verify.notice')
            ->with('success', 'Email verified! Now enter the 6-digit SMS code sent to your mobile number to create your account.');
    }

    public function verifyPendingOtp(Request $request)
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
            'token' => ['nullable', 'string', 'max:64'],
        ]);

        $pending = $this->resolvePendingRegistration($request, $validated['token'] ?? null);

        if (! $pending) {
            $msg = 'Your registration session has expired. Please register again.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 410)
                : redirect()->route('register')->withErrors(['email' => $msg]);
        }

        if ($pending->hasVerifiedPhone()) {
            if ($pending->isFullyVerified()) {
                return $this->finalizePendingRegistration($pending, $request);
            }

            $msg = 'Mobile number is already verified. Please click the verification link sent to your email.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg, 'completed' => false])
                : redirect()->route('registration.verify.notice')->with('success', $msg);
        }

        if ($pending->phone_otp_attempts >= 5) {
            throw ValidationException::withMessages([
                'otp' => 'Too many incorrect SMS attempts. Please request a new verification code.',
            ]);
        }

        if ($pending->isPhoneOtpExpired()) {
            throw ValidationException::withMessages([
                'otp' => 'This SMS code has expired. Please request a new code.',
            ]);
        }

        if (! Hash::check($validated['otp'], $pending->phone_otp_hash)) {
            $pending->increment('phone_otp_attempts');

            throw ValidationException::withMessages([
                'otp' => 'The SMS verification code is incorrect.',
            ]);
        }

        $pending->forceFill(['phone_verified_at' => now()])->save();

        if ($pending->isFullyVerified()) {
            return $this->finalizePendingRegistration($pending, $request);
        }

        $msg = 'Mobile number verified! Please click the verification link sent to your email to finish creating your account.';

        return $request->expectsJson()
            ? response()->json(['message' => $msg, 'completed' => false, 'phone_verified' => true, 'email_verified' => false])
            : redirect()->route('registration.verify.notice')->with('success', $msg);
    }

    public function resendPendingOtp(Request $request, SmsService $sms)
    {
        $pending = $this->resolvePendingRegistration($request, $request->input('token'));
        if (! $pending) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Your registration session has expired. Please register again.'], 410)
                : redirect()->route('register')->withErrors(['email' => 'Your registration session has expired. Please register again.']);
        }

        if ($pending->hasVerifiedPhone()) {
            $msg = 'Your mobile number is already verified.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 400)
                : back()->with('success', $msg);
        }

        if (($wait = $this->cooldownRemaining($pending->last_sms_sent_at)) > 0) {
            $unit = $wait === 1 ? 'second' : 'seconds';
            $msg = "Please wait {$wait} {$unit} before requesting a new SMS code.";

            return $request->expectsJson()
                ? response()->json(['message' => $msg, 'retry_after' => $wait], 429)
                : back()->with('warning', $msg)->with('cooldown_type', 'sms');
        }

        $otp = (string) random_int(100000, 999999);
        $pending->forceFill([
            'phone_otp_hash' => Hash::make($otp),
            'phone_otp_expires_at' => now()->addMinutes(10),
            'phone_otp_attempts' => 0,
            'last_sms_sent_at' => now(),
        ])->save();

        if (! $this->attemptSms(
            fn () => $sms->sendOtp($pending->phone, $otp, 10),
            'Failed to resend registration SMS OTP.',
            ['pending_registration_id' => $pending->id],
        )) {
            $msg = 'We could not send the SMS code right now. Please try again later.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 503)
                : back()->with('warning', $msg);
        }

        $msg = 'A new 6-digit SMS code has been sent to your mobile number.';

        return $request->expectsJson()
            ? response()->json(['message' => $msg])
            : back()->with('success', $msg);
    }

    public function resendPendingEmail(Request $request)
    {
        $pending = $this->resolvePendingRegistration($request, $request->input('token'));
        if (! $pending) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Your registration session has expired. Please register again.'], 410)
                : redirect()->route('register')->withErrors(['email' => 'Your registration session has expired. Please register again.']);
        }

        if ($pending->hasVerifiedEmail()) {
            $msg = 'Your email address is already verified.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 400)
                : back()->with('success', $msg);
        }

        if (($wait = $this->cooldownRemaining($pending->last_email_sent_at)) > 0) {
            $unit = $wait === 1 ? 'second' : 'seconds';
            $msg = "Please wait {$wait} {$unit} before requesting a new verification email.";

            return $request->expectsJson()
                ? response()->json(['message' => $msg, 'retry_after' => $wait], 429)
                : back()->with('warning', $msg)->with('cooldown_type', 'email');
        }

        $plainEmailToken = Str::random(40);
        $pending->forceFill([
            'email_token_hash' => hash('sha256', $plainEmailToken),
            'last_email_sent_at' => now(),
        ])->save();

        $verificationUrl = $this->pendingEmailVerificationUrl($pending, $plainEmailToken);

        if (! $this->attemptMail(
            fn () => Notification::route('mail', $pending->email)
                ->notify(new PendingRegistrationVerifyEmail($pending->name, $verificationUrl, 60)),
            'Failed to resend pending registration verification email.',
            ['pending_registration_id' => $pending->id],
        )) {
            $msg = 'We could not send the verification email right now. Please try again later.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 503)
                : back()->with('warning', $msg);
        }

        $msg = 'A new verification link has been sent to your email address.';

        return $request->expectsJson()
            ? response()->json(['message' => $msg])
            : back()->with('success', $msg);
    }

    public function cancelPendingRegistration(Request $request)
    {
        if ($pending = $this->resolvePendingRegistration($request, $request->input('token'))) {
            $pending->delete();
        }

        $request->session()->forget('pending_registration_token');

        return redirect()->route('register');
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $email = Str::lower($validated['email']);
        $key = Str::transliterate(Str::lower($email).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Try again in {$seconds} seconds.",
            ]);
        }

        if (! Auth::attempt([
            'email' => $email,
            'password' => $validated['password'],
        ], (bool) ($validated['remember'] ?? false))) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'The email or password is incorrect.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'You are now logged in.',
            'user' => $request->user()->only(['id', 'name', 'email', 'phone', 'role']),
            'redirect' => $request->user()->isStaff()
                ? '/admin'
                : ($request->user()->profile_completed_at ? '/dashboard' : '/profile/create'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson()
            ? response()->json(['message' => 'You have been logged out.'])
            : redirect('/');
    }

    public function sendVerification(Request $request): JsonResponse
    {
        abort_if($request->user()->hasVerifiedEmail(), 400, 'Email is already verified.');

        if (! $this->attemptMail(
            fn () => $request->user()->sendEmailVerificationNotification(),
            'Failed to resend verification email.',
            ['user_id' => $request->user()->id],
        )) {
            return response()->json([
                'message' => 'We could not send the verification email right now. Please try again later or contact clinic support.',
            ], 503);
        }

        return response()->json(['message' => 'Verification email sent.']);
    }

    public function verifyEmail(Request $request, string $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            abort(403, 'Invalid verification link.');
        }

        if (Auth::check() && (string) Auth::id() !== (string) $user->getKey()) {
            abort(403, 'This verification link belongs to a different account.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        if (! Auth::check()) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        if ($user->isStaff()) {
            return redirect()->route('admin.dashboard')
                ->with('success', 'Email verified.');
        }

        return redirect()->route($user->profile_completed_at ? 'dashboard' : 'profile.create')
            ->with('success', $user->profile_completed_at
                ? 'Email verified.'
                : 'Email verified. Please complete your profile.');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email:rfc']]);
        $email = Str::lower(trim($request->string('email')->toString()));
        $key = 'password-reset|'.$request->ip().'|'.$email;
        $message = 'If an account exists with that email, a reset link has been sent.';

        if (! RateLimiter::tooManyAttempts($key, 3)) {
            RateLimiter::hit($key, 900);
            if (! $this->attemptMail(
                fn () => Password::sendResetLink(['email' => $email]),
                'Failed to send password reset email.',
                ['email' => $email],
            )) {
                $errorMsg = 'We could not send the reset email right now. Please try again later.';

                return $request->expectsJson()
                    ? response()->json(['message' => $errorMsg], 503)
                    : back()->withErrors(['email' => $errorMsg]);
            }
        }

        return $request->expectsJson()
            ? response()->json(['message' => $message])
            : back()->with('status', $message);
    }

    public function resetPassword(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim($request->string('email')->toString())),
        ]);
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email:rfc'],
            'password' => $this->strongPasswordRules(),
        ]);

        $status = Password::reset($validated, function (User $user, string $password): void {
            $user->forceFill(['password' => Hash::make($password)])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $this->attemptMail(
                fn () => $user->notify(new PasswordChangedNotification()),
                'Failed to send password changed notification.',
                ['user_id' => $user->id],
                warning: true,
            );
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Password reset successfully.', 'redirect' => '/'])
            : redirect('/')->with('success', 'Password reset successfully. Please log in.');
    }

    private function attemptMail(callable $send, string $errorMessage, array $context = [], bool $warning = false): bool
    {
        try {
            $send();

            return true;
        } catch (\Throwable $e) {
            $logContext = array_merge($context, ['error' => $e->getMessage()]);
            if ($warning) {
                Log::warning($errorMessage, $logContext);
            } else {
                Log::error($errorMessage, $logContext);
            }

            return false;
        }
    }

    private function cooldownRemaining(?\Carbon\CarbonInterface $lastSentAt, int $cooldownSeconds = 60): int
    {
        if (! $lastSentAt) {
            return 0;
        }

        $expiresAt = $lastSentAt->copy()->addSeconds($cooldownSeconds);
        if ($expiresAt->lte(now())) {
            return 0;
        }

        return max(1, (int) ceil(now()->diffInSeconds($expiresAt)));
    }

    private function attemptSms(callable $send, string $errorMessage, array $context = []): bool
    {
        try {
            $send();

            return true;
        } catch (\Throwable $e) {
            Log::warning($errorMessage, array_merge($context, ['error' => $e->getMessage()]));

            return false;
        }
    }

    private function finalizePendingRegistration(PendingRegistration $pending, Request $request)
    {
        $user = DB::transaction(function () use ($pending): ?User {
            $dob = $pending->date_of_birth->format('Y-m-d');

            $conflict = User::where('email', $pending->email)
                ->orWhere('canonical_email', $pending->canonical_email)
                ->orWhere('phone', $pending->phone)
                ->orWhere(function ($query) use ($pending, $dob): void {
                    $query->whereRaw('LOWER(name) = ?', [Str::lower($pending->name)])
                        ->where(function ($sub) use ($pending, $dob): void {
                            $sub->whereDate('date_of_birth', $dob)
                                ->orWhere('phone', $pending->phone);
                        });
                })
                ->exists();

            if ($conflict) {
                $pending->delete();

                return null;
            }

            $user = User::create([
                'name' => $pending->name,
                'email' => $pending->email,
                'canonical_email' => $pending->canonical_email,
                'phone' => $pending->phone,
                'date_of_birth' => $dob,
                'password' => $pending->password,
                'email_verified_at' => $pending->email_verified_at,
                'phone_verified_at' => $pending->phone_verified_at,
            ]);

            $pending->delete();

            return $user;
        });

        $request->session()->forget('pending_registration_token');

        if (! $user) {
            $msg = 'An account matching your details was already created. Please sign in or reset your password.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 409)
                : redirect()->route('register')->withErrors(['duplicate_account' => $msg]);
        }

        event(new Verified($user));
        Auth::login($user);
        $request->session()->regenerate();

        $message = config('services.sms.otp_enabled', false)
            ? 'Email and mobile number verified! Please complete your profile.'
            : 'Email verified! Please complete your profile.';

        return $request->expectsJson()
            ? response()->json([
                'message' => $message,
                'completed' => true,
                'redirect' => route('profile.create', [], false),
                'user' => $user->only(['id', 'name', 'email', 'phone', 'date_of_birth']),
            ], 201)
            : redirect()->route('profile.create')->with('success', $message);
    }

    private function resolvePendingRegistration(Request $request, ?string $token = null): ?PendingRegistration
    {
        $token = $token ?: $request->query('token') ?: $request->session()->get('pending_registration_token');
        if (! is_string($token) || trim($token) === '') {
            return null;
        }

        $pending = PendingRegistration::where('token', trim($token))->first();
        if (! $pending || $pending->isExpired()) {
            if ($pending) {
                $pending->delete();
            }
            $request->session()->forget('pending_registration_token');

            return null;
        }

        $request->session()->put('pending_registration_token', $pending->token);

        return $pending;
    }

    private function pendingEmailVerificationUrl(PendingRegistration $pending, string $plainEmailToken): string
    {
        return URL::temporarySignedRoute(
            'registration.verify-email',
            $pending->expires_at,
            ['token' => $pending->token, 'emailToken' => $plainEmailToken]
        );
    }

    private function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return '***';
        }
        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, min(2, strlen($local)));

        return $visible.str_repeat('*', max(3, strlen($local) - strlen($visible))).'@'.$domain;
    }

    private function maskPhone(string $phone): string
    {
        $len = strlen($phone);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', $len - 4).substr($phone, -4);
    }

    private function strongPasswordRules(): array
    {
        return [
            'required',
            'string',
            'min:8',
            'confirmed',
            'regex:/[A-Z]/',
            'regex:/[0-9]/',
            'regex:/[^A-Za-z0-9]/',
        ];
    }
}

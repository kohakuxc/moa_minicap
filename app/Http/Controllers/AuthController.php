<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
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

    public function register(Request $request)
    {
        $registrationKey = 'registration|'.$request->ip();
        abort_if(RateLimiter::tooManyAttempts($registrationKey, 10), 429, 'Please try again later.');
        RateLimiter::hit($registrationKey, 60);
        $request->merge([
            'name' => Str::squish($request->string('name')->toString()),
            'email' => Str::lower(trim($request->string('email')->toString())),
            'phone' => preg_replace('/\s+/', ' ', trim($request->string('phone')->toString())),
        ]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->whereNotNull('phone')],
            'password' => $this->strongPasswordRules(),
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $user->sendEmailVerificationNotification();

        $response = [
            'message' => 'Your account has been created.',
            'user' => $user->only(['id', 'name', 'email', 'phone']),
        ];

        return $request->expectsJson()
            ? response()->json($response, 201)
            : redirect('/email/verify')->with('success', $response['message']);
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
        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent.']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email:rfc']]);
        $email = Str::lower(trim($request->string('email')->toString()));
        $key = 'password-reset|'.$request->ip().'|'.$email;
        $message = 'If an account exists with that email, a reset link has been sent.';

        if (! RateLimiter::tooManyAttempts($key, 3)) {
            RateLimiter::hit($key, 900);
            Password::sendResetLink(['email' => $email]);
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
            $user->notify(new PasswordChangedNotification());
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Password reset successfully.', 'redirect' => '/'])
            : redirect('/')->with('success', 'Password reset successfully. Please log in.');
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

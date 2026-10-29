<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $registrationKey = 'registration|'.$request->ip();
        abort_if(RateLimiter::tooManyAttempts($registrationKey, 10), 429, 'Please try again later.');
        RateLimiter::hit($registrationKey, 60);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Your account has been created.',
            'user' => $user->only(['id', 'name', 'email', 'phone']),
        ], 201);
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
            'user' => $request->user()->only(['id', 'name', 'email', 'phone']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'You have been logged out.']);
    }

    public function sendVerification(Request $request): JsonResponse
    {
        abort_if($request->user()->hasVerifiedEmail(), 400, 'Email is already verified.');
        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email:rfc']]);
        $key = 'password-reset|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'If an account exists, reset instructions will be sent.'])
                : back()->with('status', 'If an account exists, reset instructions will be sent.');
        }

        RateLimiter::hit($key, 60);
        Password::sendResetLink(['email' => Str::lower($request->string('email')->toString())]);

        return response()->json(['message' => 'If an account exists, reset instructions will be sent.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email:rfc'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($validated, function (User $user, string $password): void {
            $user->forceFill(['password' => Hash::make($password)])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Password reset successfully.'])
            : redirect('/')->with('success', 'Password reset successfully. Please log in.');
    }
}

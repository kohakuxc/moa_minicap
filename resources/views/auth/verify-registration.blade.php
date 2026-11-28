@extends('auth.layout')

@section('title', 'Verify your registration')

@section('content')
    <div class="reg-page-head overview-head">
        <div>
            <h1>Verify your <em>account</em></h1>
            <p>Complete both checks below before your patient account is activated.</p>
        </div>
    </div>

    @if(session('success'))
        <p class="success-message" style="padding: 10px 12px; border-radius: 4px; margin-bottom: 14px;">{{ session('success') }}</p>
    @endif
    @if(session('warning'))
        <p class="auth-error" style="margin-bottom: 14px;">{{ session('warning') }}</p>
    @endif

    <div style="display: grid; gap: 12px; margin-bottom: 20px;">
        <div style="padding: 12px 14px; border: 1px solid #d5e0fa; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
            <div>
                <div style="font-weight: 600; font-size: 13px; color: #1b2b73;">1. Email verification</div>
                <div style="font-size: 12px; color: #5b6b8c;">Link sent to {{ $maskedEmail }}</div>
            </div>
            <span style="font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 999px; {{ $pending->hasVerifiedEmail() ? 'background:#e6f4f1; color:#1c6b5d;' : 'background:#eef2ff; color:#2446a8;' }}">
                {{ $pending->hasVerifiedEmail() ? 'Verified' : 'Pending' }}
            </span>
        </div>

        <div style="padding: 12px 14px; border: 1px solid #d5e0fa; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
            <div>
                <div style="font-weight: 600; font-size: 13px; color: #1b2b73;">2. Mobile SMS OTP</div>
                <div style="font-size: 12px; color: #5b6b8c;">6-digit code sent to {{ $maskedPhone }}</div>
            </div>
            <span style="font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 999px; {{ $pending->hasVerifiedPhone() ? 'background:#e6f4f1; color:#1c6b5d;' : 'background:#eef2ff; color:#2446a8;' }}">
                {{ $pending->hasVerifiedPhone() ? 'Verified' : 'Pending' }}
            </span>
        </div>
    </div>

    @if(!$pending->hasVerifiedPhone())
        <form class="auth-flow-form" method="POST" action="{{ route('registration.verify-otp') }}" style="margin-bottom: 14px;">
            @csrf
            <input type="hidden" name="token" value="{{ $pending->token }}">
            <label>Enter 6-digit SMS code<span class="required"></span>
                <input type="text" name="otp" inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="123456" autocomplete="one-time-code" required>
                @error('otp')<span class="auth-error">{{ $message }}</span>@enderror
            </label>
            <button class="button button-teal auth-submit" type="submit">Verify SMS code</button>
        </form>

        <form method="POST" action="{{ route('registration.resend-otp') }}" style="margin-bottom: 10px;">
            @csrf
            <input type="hidden" name="token" value="{{ $pending->token }}">
            <button class="text-button" type="submit">Resend SMS code</button>
        </form>
    @endif

    @if(!$pending->hasVerifiedEmail())
        <form method="POST" action="{{ route('registration.resend-email') }}" style="margin-bottom: 10px;">
            @csrf
            <input type="hidden" name="token" value="{{ $pending->token }}">
            <button class="text-button" type="submit">Resend email verification link</button>
        </form>
    @endif

    <form method="POST" action="{{ route('registration.cancel') }}" style="margin-top: 14px;">
        @csrf
        <input type="hidden" name="token" value="{{ $pending->token }}">
        <button class="text-button" type="submit">Cancel and start over</button>
    </form>
@endsection

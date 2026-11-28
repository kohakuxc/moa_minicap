@extends('auth.layout')

@section('title', 'Verify your registration')

@section('content')
    <div class="reg-page-head overview-head">
        <div>
            <h1>Verify your <em>account</em></h1>
            <p>
                {{ !empty($smsOtpEnabled)
                    ? 'Complete both checks below before your patient account is activated.'
                    : 'Verify your email address before your patient account is created and activated.' }}
            </p>
        </div>
    </div>

    @if(session('success'))
        <p class="success-message" style="padding: 10px 12px; border-radius: 4px; margin-bottom: 14px;">{{ session('success') }}</p>
    @endif
    @if(session('warning'))
        @php
            $cooldownType = session('cooldown_type');
            $bannerSeconds = $cooldownType === 'sms' ? ($smsCooldown ?? 0) : ($cooldownType === 'email' ? ($emailCooldown ?? 0) : 0);
        @endphp
        <p class="auth-error"
           style="margin-bottom: 14px;"
           @if($cooldownType && $bannerSeconds > 0)
               data-cooldown-banner="{{ $cooldownType }}"
               data-cooldown-seconds="{{ $bannerSeconds }}"
           @endif>{{ session('warning') }}</p>
    @endif

    <div style="display: grid; gap: 12px; margin-bottom: 20px;">
        <div style="padding: 12px 14px; border: 1px solid #d5e0fa; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
            <div>
                <div style="font-weight: 600; font-size: 13px; color: #1b2b73;">{{ !empty($smsOtpEnabled) ? '1. Email verification' : 'Email verification' }}</div>
                <div style="font-size: 12px; color: #5b6b8c;">Verification link sent to {{ $maskedEmail }}</div>
            </div>
            <span data-email-status-badge style="font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 999px; {{ $pending->hasVerifiedEmail() ? 'background:#e6f4f1; color:#1c6b5d;' : 'background:#eef2ff; color:#2446a8;' }}">
                {{ $pending->hasVerifiedEmail() ? 'Verified' : 'Pending' }}
            </span>
        </div>

        @if(!empty($smsOtpEnabled))
            <div style="padding: 12px 14px; border: 1px solid #d5e0fa; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                <div>
                    <div style="font-weight: 600; font-size: 13px; color: #1b2b73;">2. Mobile SMS OTP</div>
                    <div style="font-size: 12px; color: #5b6b8c;">6-digit code sent to {{ $maskedPhone }}</div>
                </div>
                <span data-phone-status-badge style="font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 999px; {{ $pending->hasVerifiedPhone() ? 'background:#e6f4f1; color:#1c6b5d;' : 'background:#eef2ff; color:#2446a8;' }}">
                    {{ $pending->hasVerifiedPhone() ? 'Verified' : 'Pending' }}
                </span>
            </div>
        @endif
    </div>

    <p data-live-status-text style="font-size: 12px; color: #5b6b8c; margin: -6px 0 16px;" aria-live="polite">
        Waiting for verification… this page will continue automatically once verified.
    </p>

    @if(!empty($smsOtpEnabled) && !$pending->hasVerifiedPhone())
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
            <button
                class="text-button"
                type="submit"
                data-resend-countdown="{{ $smsCooldown ?? 0 }}"
                data-base-label="Resend SMS code"
                @disabled(($smsCooldown ?? 0) > 0)
            >{{ ($smsCooldown ?? 0) > 0 ? "Resend SMS code ({$smsCooldown}s)" : 'Resend SMS code' }}</button>
        </form>
    @endif

    @if(!$pending->hasVerifiedEmail())
        <form method="POST" action="{{ route('registration.resend-email') }}" style="margin-bottom: 10px;" data-resend-email-form>
            @csrf
            <input type="hidden" name="token" value="{{ $pending->token }}">
            <button
                class="text-button"
                type="submit"
                data-resend-countdown="{{ $emailCooldown ?? 0 }}"
                data-base-label="Resend email verification link"
                @disabled(($emailCooldown ?? 0) > 0)
            >{{ ($emailCooldown ?? 0) > 0 ? "Resend email verification link ({$emailCooldown}s)" : 'Resend email verification link' }}</button>
        </form>
    @endif

    <form method="POST" action="{{ route('registration.cancel') }}" style="margin-top: 14px;">
        @csrf
        <input type="hidden" name="token" value="{{ $pending->token }}">
        <button class="text-button" type="submit">Cancel and start over</button>
    </form>
@endsection

@push('scripts')
<script>
(() => {
    const startCountdown = (initialSeconds, onTick, onDone) => {
        if (!Number.isFinite(initialSeconds) || initialSeconds <= 0) return;
        const targetMs = Date.now() + initialSeconds * 1000;
        const update = () => {
            const remaining = Math.max(0, Math.ceil((targetMs - Date.now()) / 1000));
            if (remaining <= 0) {
                onDone();
                return false;
            }
            onTick(remaining);
            return true;
        };
        if (!update()) return;
        const timer = window.setInterval(() => {
            if (!update()) window.clearInterval(timer);
        }, 250);
    };

    document.querySelectorAll('[data-resend-countdown]').forEach((button) => {
        const seconds = parseInt(button.dataset.resendCountdown || '0', 10);
        const baseLabel = button.dataset.baseLabel || button.textContent.trim();
        startCountdown(
            seconds,
            (remaining) => {
                button.disabled = true;
                button.style.opacity = '0.55';
                button.style.cursor = 'not-allowed';
                button.textContent = `${baseLabel} (${remaining}s)`;
            },
            () => {
                button.disabled = false;
                button.style.opacity = '';
                button.style.cursor = '';
                button.textContent = baseLabel;
            }
        );
    });

    const banner = document.querySelector('[data-cooldown-banner]');
    if (banner) {
        const type = banner.dataset.cooldownBanner;
        const seconds = parseInt(banner.dataset.cooldownSeconds || '0', 10);
        const noun = type === 'email' ? 'verification email' : 'SMS code';
        startCountdown(
            seconds,
            (remaining) => {
                const unit = remaining === 1 ? 'second' : 'seconds';
                banner.textContent = `Please wait ${remaining} ${unit} before requesting a new ${noun}.`;
            },
            () => {
                banner.textContent = `You can now request a new ${noun}.`;
                banner.classList.remove('auth-error');
                banner.classList.add('success-message');
                banner.style.padding = '10px 12px';
                banner.style.borderRadius = '4px';
            }
        );
    }

    const statusUrl = @json(route('registration.status', ['token' => $pending->token], false));
    const emailBadge = document.querySelector('[data-email-status-badge]');
    const phoneBadge = document.querySelector('[data-phone-status-badge]');
    const resendEmailForm = document.querySelector('[data-resend-email-form]');
    const liveStatusText = document.querySelector('[data-live-status-text]');
    let pollTimer = null;
    let checking = false;

    const markBadgeVerified = (badge) => {
        if (!badge) return;
        badge.textContent = 'Verified';
        badge.style.background = '#e6f4f1';
        badge.style.color = '#1c6b5d';
    };

    const pollStatus = async () => {
        if (checking) return;
        checking = true;
        try {
            const response = await fetch(statusUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!response.ok) return;
            const data = await response.json();

            if (data.status === 'completed' && data.redirect) {
                if (pollTimer) window.clearInterval(pollTimer);
                markBadgeVerified(emailBadge);
                markBadgeVerified(phoneBadge);
                if (liveStatusText) {
                    liveStatusText.textContent = 'Verified! Redirecting to your account…';
                    liveStatusText.style.color = '#1c6b5d';
                }
                window.location.assign(data.redirect);
                return;
            }

            if (data.status === 'expired' && data.redirect) {
                if (pollTimer) window.clearInterval(pollTimer);
                window.location.assign(data.redirect);
                return;
            }

            if (data.status === 'pending') {
                if (data.email_verified) {
                    markBadgeVerified(emailBadge);
                    if (resendEmailForm) resendEmailForm.hidden = true;
                }
                if (data.phone_verified) {
                    markBadgeVerified(phoneBadge);
                }
            }
        } catch (_) {
            // Ignore transient network errors and retry on next poll
        } finally {
            checking = false;
        }
    };

    pollTimer = window.setInterval(() => {
        if (!document.hidden) pollStatus();
    }, 2500);

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) pollStatus();
    });
    window.addEventListener('focus', pollStatus);
})();
</script>
@endpush

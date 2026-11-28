@extends('auth.layout')

@section('title', 'Create your account')

@section('content')
    <div class="reg-page-head overview-head">
    <div>
        <h1>Create your <em>account</em></h1>
        <p>Set up secure access to appointments, prescriptions, and your care history.</p>
    </div>
    </div>

    @error('duplicate_account')
        <div class="auth-form-note" role="alert" style="margin: 0 0 16px; padding: 12px 14px; border: 1px solid #d59b8d; border-radius: 4px; background: #fff6f4; color: #7a2d23;">
            <strong>Possible existing account:</strong> {{ $message }}
            <div style="margin-top: 8px; display: flex; gap: 14px; flex-wrap: wrap;">
                <a href="{{ route('password.request') }}" style="color: #7a2d23; font-weight: 600; text-decoration: underline;">Reset your password</a>
                <a href="/" style="color: #1b2b73; font-weight: 600; text-decoration: underline;">Sign in instead</a>
            </div>
        </div>
    @enderror

    <form class="auth-flow-form" method="POST" action="{{ route('auth.register') }}" data-password-form>
        @csrf
        <label>Full name<span class="required"></span><input type="text" name="name" value="{{ old('name') }}" autocomplete="name"
                required>@error('name')<span class="auth-error">{{ $message }}</span>@enderror</label>
        <label>Date of birth<span class="required"></span><input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"
                max="{{ now()->subDay()->toDateString() }}" min="1900-01-01" autocomplete="bday" required>@error('date_of_birth')<span class="auth-error">{{ $message }}</span>@enderror</label>
        <label>Email address<span class="required"></span><input type="email" name="email" value="{{ old('email') }}" autocomplete="email"
                required>@error('email')<span class="auth-error">{{ $message }}</span>@enderror</label>
        <label>Mobile number<span class="required"></span><input type="tel" name="phone" value="{{ old('phone') }}" placeholder="e.g. 0917 123 4567" autocomplete="tel"
                required>@error('phone')<span class="auth-error">{{ $message }}</span>@enderror</label>
        <label>Password<span class="required"></span><span class="password-field"><input type="password" name="password" autocomplete="new-password"
                    required data-password><button type="button" class="password-toggle"
                    data-password-toggle>Show</button></span>@error('password')<span
                    class="auth-error">{{ $message }}</span>@enderror</label>
        <div class="password-strength" data-password-strength aria-live="polite"><span>Password must include:</span>
            <ul>
                <li data-rule="length">8 characters</li>
                <li data-rule="uppercase">1 uppercase letter</li>
                <li data-rule="number">1 number</li>
                <li data-rule="special">1 special character</li>
            </ul>
        </div>
        <label><span class="required">Confirm password</span><span class="password-field"><input type="password" name="password_confirmation"
                    autocomplete="new-password" required data-password-confirm><button type="button" class="password-toggle"
                    data-password-toggle>Show</button></span>@error('password_confirmation')<span
                    class="auth-error">{{ $message }}</span>@enderror</label>
        <label class="terms-field"><input type="checkbox" name="terms" value="1" required @checked(old('terms'))><span>I
                agree to the <a href="#terms">Terms of Service</a> and <a href="#privacy">Data Privacy
                    Policy</a>.</span></label>
        @error('terms')<span class="auth-error">{{ $message }}</span>@enderror

        @if(!empty($turnstileEnabled) && !empty($turnstileSiteKey))
            <div class="turnstile-wrap">
                <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}"></div>
                @error('cf-turnstile-response')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
        @endif

        @if(($errors->has('email') || $errors->has('phone')) && !$errors->has('duplicate_account'))
            <p class="auth-form-note">That email or mobile number may already be registered. Check your details, <a href="{{ route('password.request') }}">reset your password</a>, or <a href="/">sign in instead</a>.</p>
        @endif
        <button class="button button-teal auth-submit" type="submit">Continue to verification</button>
    </form>
    <p class="auth-flow-footer">Already have an account? <a href="/">Log in</a></p>
@endsection

@push('scripts')
    @if(!empty($turnstileEnabled) && !empty($turnstileSiteKey))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
    <script>document.querySelectorAll('[data-password-toggle]').forEach((toggle) => toggle.addEventListener('click', () => { const input = toggle.parentElement.querySelector('input'); const visible = input.type === 'text'; input.type = visible ? 'password' : 'text'; toggle.textContent = visible ? 'Show' : 'Hide'; })); const password = document.querySelector('[data-password]'); const rules = { length: (value) => value.length >= 8, uppercase: (value) => /[A-Z]/.test(value), number: (value) => /[0-9]/.test(value), special: (value) => /[^A-Za-z0-9]/.test(value) }; password?.addEventListener('input', () => Object.entries(rules).forEach(([name, check]) => document.querySelector(`[data-rule="${name}"]`)?.classList.toggle('is-valid', check(password.value))));</script>
@endpush

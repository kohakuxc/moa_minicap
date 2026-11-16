@extends('auth.layout')

@section('title', 'Choose a new password')

@section('content')
    <div class="auth-flow-heading">
        <h1>Choose a new <em>password.</em></h1>
        <p>Use a strong password you have not used for another account.</p>
    </div>
    <form class="auth-flow-form" method="POST" action="{{ route('password.update') }}" data-password-form>@csrf<input
            type="hidden" name="token" value="{{ $token }}"><label>Email address<input type="email" name="email"
                value="{{ $email }}" autocomplete="email" required>@error('email')<span
                class="auth-error">{{ $message }}</span>@enderror</label><label>New password<span
                class="password-field"><input type="password" name="password" autocomplete="new-password" required
                    data-password><button type="button" class="password-toggle"
                    data-password-toggle>Show</button></span>@error('password')<span
                    class="auth-error">{{ $message }}</span>@enderror</label>
        <div class="password-strength" data-password-strength aria-live="polite"><span>Password must include:</span>
            <ul>
                <li data-rule="length">8 characters</li>
                <li data-rule="uppercase">1 uppercase letter</li>
                <li data-rule="number">1 number</li>
                <li data-rule="special">1 special character</li>
            </ul>
        </div><label>Confirm password<span class="password-field"><input type="password" name="password_confirmation"
                    autocomplete="new-password" required data-password-confirm><button type="button" class="password-toggle"
                    data-password-toggle>Show</button></span>@error('password_confirmation')<span
                    class="auth-error">{{ $message }}</span>@enderror</label><button class="button button-teal auth-submit"
            type="submit">Reset password <span>→</span></button>
    </form>
    <p class="auth-flow-footer"><a href="/">Return to login</a></p>
@endsection

@push('scripts')
    <script>document.querySelectorAll('[data-password-toggle]').forEach((toggle) => toggle.addEventListener('click', () => { const input = toggle.parentElement.querySelector('input'); const visible = input.type === 'text'; input.type = visible ? 'password' : 'text'; toggle.textContent = visible ? 'Show' : 'Hide'; })); const password = document.querySelector('[data-password]'); const rules = { length: (value) => value.length >= 8, uppercase: (value) => /[A-Z]/.test(value), number: (value) => /[0-9]/.test(value), special: (value) => /[^A-Za-z0-9]/.test(value) }; password?.addEventListener('input', () => Object.entries(rules).forEach(([name, check]) => document.querySelector(`[data-rule="${name}"]`)?.classList.toggle('is-valid', check(password.value))));</script>
@endpush
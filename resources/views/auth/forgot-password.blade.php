@extends('auth.layout')

@section('title', 'Forgot password')

@section('content')
<div class="auth-flow-heading"><div class="eyebrow">ACCOUNT RECOVERY</div><h1>Reset your <em>password.</em></h1><p>Enter your email and we will send reset instructions if an account exists.</p></div>
@if(session('status'))<p class="portal-feedback" role="status">{{ session('status') }}</p>@endif
<form class="auth-flow-form" method="POST" action="{{ route('password.email') }}">@csrf<label>Email address<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>@error('email')<span class="auth-error">{{ $message }}</span>@enderror</label><button class="button button-teal auth-submit" type="submit">Send reset link <span>→</span></button></form>
<p class="auth-flow-footer"><a href="/">Return to login</a></p>
@endsection
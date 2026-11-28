<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>{{ ($editing ?? false) ? 'My profile' : 'Create your profile' }} · MOA</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Alegreya+SC:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="/app.css">
</head>
<body>
<main class="auth-page">
	<section class="auth-page-card profile-card">
		<div class="profile-card-header">
			<a class="brand" href="{{ auth()->check() ? (auth()->user()->isStaff() ? route('admin.dashboard', [], false) : route('dashboard', [], false)) : '/' }}"><span class="brand-mark"><i></i><i></i></span>MOA</a>
			<span class="profile-step">{{ ($editing ?? false) ? 'YOUR ACCOUNT' : 'STEP 1 OF 1' }}</span>
		</div>
		<h1>{{ ($editing ?? false) ? 'My profile.' : 'Complete your profile.' }}</h1>
		<p class="profile-intro">{{ ($editing ?? false) ? 'Keep your contact details up to date so your care team can reach you.' : 'Add your details once so your care team can keep your appointments and records accurate.' }}</p>
		@if(session('success'))<p class="success-message">{{ session('success') }}</p>@endif
		<form class="profile-form" method="POST" action="{{ route('profile.update', [], false) }}">
			@csrf
			@method('PATCH')
			<div class="profile-form-grid">
				<label>Full name<input name="name" value="{{ old('name', $user->name) }}" autocomplete="name" required>@error('name')<span class="auth-error">{{ $message }}</span>@enderror</label>
				<label>Date of birth<input name="date_of_birth" type="date" value="{{ old('date_of_birth', optional($user->date_of_birth)->format('Y-m-d')) }}" max="{{ now()->subDay()->toDateString() }}" min="1900-01-01" autocomplete="bday" required>@error('date_of_birth')<span class="auth-error">{{ $message }}</span>@enderror</label>
				<label>Email address<input name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email" required>@error('email')<span class="auth-error">{{ $message }}</span>@enderror</label>
				<label>Phone number<input name="phone" value="{{ old('phone', $user->phone) }}" autocomplete="tel" required>@error('phone')<span class="auth-error">{{ $message }}</span>@enderror</label>
			</div>
			<button class="button button-teal" type="submit">{{ ($editing ?? false) ? 'Save changes' : 'Save profile and continue' }}</button>
		</form>
	</section>
</main>
</body>
</html>

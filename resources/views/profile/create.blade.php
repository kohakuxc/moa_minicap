<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>{{ ($editing ?? false) ? 'My profile' : 'Create your profile' }} · MOA</title>
	<link rel="stylesheet" href="/app.css">
</head>
<body>
<main class="auth-page">
	<section class="auth-page-card profile-card">
		<div class="profile-card-header">
			<div class="brand"><span class="brand-mark"><i></i><i></i></span>MOA</div>
			<span class="profile-step">{{ ($editing ?? false) ? 'YOUR ACCOUNT' : 'STEP 1 OF 1' }}</span>
		</div>
		<h1>{{ ($editing ?? false) ? 'My <em>profile.</em>' : 'Complete your <em>profile.</em>' }}</h1>
		<p class="profile-intro">{{ ($editing ?? false) ? 'Keep your contact details up to date so your care team can reach you.' : 'Add your details once so your care team can keep your appointments and records accurate.' }}</p>
		<form class="profile-form" method="POST" action="{{ route('profile.update', [], false) }}">
			@csrf
			@method('PATCH')
			<div class="profile-form-grid">
				<label>Full name<input name="name" value="{{ old('name', $user->name) }}" autocomplete="name" required>@error('name')<span class="auth-error">{{ $message }}</span>@enderror</label>
				<label>Email address<input name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email" required>@error('email')<span class="auth-error">{{ $message }}</span>@enderror</label>
				<label>Phone number<input name="phone" value="{{ old('phone', $user->phone) }}" autocomplete="tel" required>@error('phone')<span class="auth-error">{{ $message }}</span>@enderror</label>
			</div>
			<button class="button button-teal" type="submit">{{ ($editing ?? false) ? 'Save changes' : 'Save profile and continue' }}</button>
		</form>
	</section>
</main>
</body>
</html>

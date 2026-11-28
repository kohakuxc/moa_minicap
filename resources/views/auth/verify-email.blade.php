<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verify email · MOA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alegreya+SC:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/app.css">
</head>

<body>
    <main class="auth-page">
        <div class="auth-page-card">
            <a class="auth-brand" href="{{ auth()->check() ? (auth()->user()->isStaff() ? route('admin.dashboard', [], false) : route('dashboard', [], false)) : '/' }}" style="margin-bottom: 20px; display: inline-flex;"><span class="brand-mark"><i></i><i></i></span><strong>MOA</strong></a>
            <h1>Verify your <em>email.</em></h1>
            <p>We sent a verification link to your email address. Verify it before accessing patient records.</p>
            @if(session('success') || session('message'))
            <p class="success-message">{{ session('success') ?: session('message') }}</p>@endif
            <form method="POST" action="{{ route('verification.send') }}" data-verification-form>
                @csrf
                <button class="button button-teal" type="submit" data-verification-submit>Send a new link</button>
                <p class="success-message" data-verification-feedback role="status" hidden></p>
            </form>
            <form method="POST" action="{{ route('auth.logout') }}">@csrf<button class="text-button">Log out</button>
            </form>
        </div>
    </main>
    <script>document.querySelector('[data-verification-form]')?.addEventListener('submit',async(event)=>{event.preventDefault();const form=event.currentTarget,button=form.querySelector('[data-verification-submit]'),feedback=form.querySelector('[data-verification-feedback]');button.disabled=true;button.textContent='Sending…';feedback.hidden=true;try{const response=await fetch(form.action,{method:'POST',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},body:new FormData(form),credentials:'same-origin'});const type=response.headers.get('content-type')||'';const data=type.includes('application/json')?await response.json():{};if(!response.ok)throw new Error(data.message||'Unable to send the verification link.');feedback.textContent=data.message||'A new verification link has been sent.';feedback.hidden=false;}catch(error){feedback.textContent=error.message;feedback.classList.add('auth-error');feedback.classList.remove('success-message');feedback.hidden=false;}finally{button.disabled=false;button.textContent='Send a new link';}});</script>
</body>

</html>
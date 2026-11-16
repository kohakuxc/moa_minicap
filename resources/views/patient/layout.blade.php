<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Patient portal') · MOA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alegreya+SC:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/app.css">
</head>
<body>
<div class="patient-portal">
    <button class="patient-sidebar-backdrop" type="button" data-close-sidebar aria-label="Close navigation"></button>
    <aside class="patient-sidebar" data-sidebar>
        <div class="patient-sidebar-top">
            <a class="brand" href="{{ route('dashboard', [], false) }}"><span class="brand-mark"><i></i><i></i></span>MOA</a>
            <button class="sidebar-close" type="button" data-close-sidebar aria-label="Close navigation">×</button>
        </div>
        <div class="patient-profile-chip">
            <span class="patient-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
            <span><strong>{{ $user->name }}</strong><small>Patient account</small></span>
        </div>
        <nav class="patient-nav" aria-label="Patient portal navigation">
            <span class="patient-nav-label">Your care</span>
            <a class="patient-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard', [], false) }}"><span class="nav-icon">⌂</span>Overview</a>
            <a class="patient-nav-link {{ request()->routeIs('patient.appointments.create') ? 'active' : '' }}" href="{{ route('patient.appointments.create', [], false) }}"><span class="nav-icon">＋</span>Book appointment</a>
            <a class="patient-nav-link {{ request()->routeIs('patient.eyewear') ? 'active' : '' }}" href="{{ route('patient.eyewear', [], false) }}"><span class="nav-icon">◉</span>Eyewear</a>
            <a class="patient-nav-link {{ request()->routeIs('patient.care-services') ? 'active' : '' }}" href="{{ route('patient.care-services', [], false) }}"><span class="nav-icon">✧</span>Care services</a>
            <span class="patient-nav-label patient-nav-label-spaced">Your records</span>
            <a class="patient-nav-link {{ request()->routeIs('patient.appointments.upcoming') ? 'active' : '' }}" href="{{ route('patient.appointments.upcoming', [], false) }}"><span class="nav-icon">◷</span>Upcoming appointments</a>
            <a class="patient-nav-link {{ request()->routeIs('patient.prescriptions') ? 'active' : '' }}" href="{{ route('patient.prescriptions', [], false) }}"><span class="nav-icon">▤</span>Prescriptions</a>
            <a class="patient-nav-link {{ request()->routeIs('patient.appointments.history') ? 'active' : '' }}" href="{{ route('patient.appointments.history', [], false) }}"><span class="nav-icon">↺</span>Visit history</a>
        </nav>
        <div class="patient-sidebar-footer">
            <a class="patient-nav-link" href="{{ route('profile.create', [], false) }}"><span class="nav-icon">○</span>My profile</a>
            <button class="patient-nav-link patient-logout-link" type="button" data-open-logout><span class="nav-icon">↪</span>Log out</button>
        </div>
    </aside>
    <main class="patient-content">
        <header class="patient-mobile-header">
            <button class="sidebar-toggle" type="button" data-open-sidebar aria-label="Open navigation">☰</button>
            <a class="brand" href="{{ route('dashboard', [], false) }}"><span class="brand-mark"><i></i><i></i></span>MOA</a>
            <span class="patient-mobile-spacer"></span>
        </header>
        @yield('content')
    </main>
</div>
<div class="modal-backdrop logout-modal-backdrop" data-logout-modal hidden>
    <section class="modal logout-modal" role="dialog" aria-modal="true" aria-labelledby="logout-title" aria-describedby="logout-description">
        <button class="modal-close" type="button" data-close-logout aria-label="Close logout dialog">×</button>
        <h2 id="logout-title">Leave the portal?</h2>
        <p id="logout-description">You will need to sign in again to manage your appointments and care history.</p>
        <div class="logout-actions">
            <button class="button button-quiet" type="button" data-close-logout>Stay signed in</button>
            <form method="POST" action="{{ route('auth.logout', [], false) }}">@csrf<button class="button button-dark" type="submit">Log out</button></form>
        </div>
    </section>
</div>
<script>
const sidebar=document.querySelector('[data-sidebar]');const openSidebar=()=>{sidebar?.classList.add('is-open');document.body.classList.add('sidebar-open');};const closeSidebar=()=>{sidebar?.classList.remove('is-open');document.body.classList.remove('sidebar-open');};document.querySelector('[data-open-sidebar]')?.addEventListener('click',openSidebar);document.querySelectorAll('[data-close-sidebar]').forEach((button)=>button.addEventListener('click',closeSidebar));
const logoutModal=document.querySelector('[data-logout-modal]');const logoutTrigger=document.querySelector('[data-open-logout]');const closeLogout=()=>{logoutModal.hidden=true;logoutTrigger?.focus();};logoutTrigger?.addEventListener('click',()=>{logoutModal.hidden=false;logoutModal.querySelector('[data-close-logout]')?.focus();});logoutModal?.querySelectorAll('[data-close-logout]').forEach((button)=>button.addEventListener('click',closeLogout));logoutModal?.addEventListener('click',(event)=>{if(event.target===logoutModal)closeLogout();});document.addEventListener('keydown',(event)=>{if(event.key==='Escape'){if(logoutModal&&!logoutModal.hidden)closeLogout();else closeSidebar();}});
</script>
@stack('scripts')
</body>
</html>
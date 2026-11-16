<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Clinic overview') · MOA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alegreya+SC:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/app.css">
</head>
<body>
@php($adminUser = auth()->user())
<div class="admin-portal">
    <button class="admin-sidebar-backdrop" type="button" data-close-admin-sidebar aria-label="Close navigation"></button>
    <aside class="admin-sidebar" data-admin-sidebar>
        <div class="admin-sidebar-top">
            <a class="brand" href="{{ route('admin.dashboard', [], false) }}"><span class="brand-mark"><i></i><i></i></span>MOA</a>
            <button class="sidebar-close" type="button" data-close-admin-sidebar aria-label="Close navigation">×</button>
        </div>
        <div class="admin-profile-chip"><span class="patient-avatar">{{ strtoupper(substr($adminUser->name, 0, 1)) }}</span><span><strong>{{ $adminUser->name }}</strong><small>Clinic administrator</small></span></div>
        <nav class="patient-nav" aria-label="Clinic navigation">
            <span class="patient-nav-label">Workspace</span>
            <a class="patient-nav-link active" href="#overview"><span class="nav-icon">⌂</span>Overview</a>
            <a class="patient-nav-link" href="#availability"><span class="nav-icon">＋</span>Availability</a>
            <a class="patient-nav-link" href="#appointments"><span class="nav-icon">◷</span>Appointments</a>
            <span class="patient-nav-label patient-nav-label-spaced">Records</span>
            <a class="patient-nav-link" href="#patients"><span class="nav-icon">◉</span>Patients</a>
            <a class="patient-nav-link" href="#prescriptions"><span class="nav-icon">▤</span>Prescriptions</a>
        </nav>
        <div class="patient-sidebar-footer"><button class="patient-nav-link patient-logout-link" type="button" data-open-admin-logout><span class="nav-icon">↪</span>Log out</button></div>
    </aside>
    <main class="admin-content">
        <header class="patient-mobile-header"><button class="sidebar-toggle" type="button" data-open-admin-sidebar aria-label="Open navigation">☰</button><a class="brand" href="{{ route('admin.dashboard', [], false) }}"><span class="brand-mark"><i></i><i></i></span>MOA</a><span class="patient-mobile-spacer"></span></header>
        @yield('content')
    </main>
</div>
<div class="modal-backdrop logout-modal-backdrop" data-admin-logout-modal hidden>
    <section class="modal logout-modal" role="dialog" aria-modal="true" aria-labelledby="admin-logout-title" aria-describedby="admin-logout-description">
        <button class="modal-close" type="button" data-close-admin-logout aria-label="Close logout dialog">×</button>
        <div class="eyebrow">MOA CLINIC PORTAL</div><h2 id="admin-logout-title">Leave the portal?</h2><p id="admin-logout-description">You will need to sign in again to manage clinic operations.</p>
        <div class="logout-actions"><button class="button button-quiet" type="button" data-close-admin-logout>Stay signed in</button><form method="POST" action="{{ route('auth.logout', [], false) }}">@csrf<button class="button button-dark" type="submit">Log out</button></form></div>
    </section>
</div>
<script>
const adminSidebar=document.querySelector('[data-admin-sidebar]');const openAdminSidebar=()=>{adminSidebar?.classList.add('is-open');document.body.classList.add('admin-sidebar-open');};const closeAdminSidebar=()=>{adminSidebar?.classList.remove('is-open');document.body.classList.remove('admin-sidebar-open');};document.querySelector('[data-open-admin-sidebar]')?.addEventListener('click',openAdminSidebar);document.querySelectorAll('[data-close-admin-sidebar]').forEach((button)=>button.addEventListener('click',closeAdminSidebar));
const adminLogoutModal=document.querySelector('[data-admin-logout-modal]');const adminLogoutTrigger=document.querySelector('[data-open-admin-logout]');const closeAdminLogout=()=>{adminLogoutModal.hidden=true;adminLogoutTrigger?.focus();};adminLogoutTrigger?.addEventListener('click',()=>{adminLogoutModal.hidden=false;adminLogoutModal.querySelector('[data-close-admin-logout]')?.focus();});adminLogoutModal?.querySelectorAll('[data-close-admin-logout]').forEach((button)=>button.addEventListener('click',closeAdminLogout));adminLogoutModal?.addEventListener('click',(event)=>{if(event.target===adminLogoutModal)closeAdminLogout();});document.addEventListener('keydown',(event)=>{if(event.key==='Escape'){if(adminLogoutModal&&!adminLogoutModal.hidden)closeAdminLogout();else closeAdminSidebar();}});
</script>
@stack('scripts')
</body>
</html>
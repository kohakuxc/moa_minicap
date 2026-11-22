<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Clinic Operations') · MOA Optical Clinic</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Alegreya+SC:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="/app.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        .staff-role-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 2px 7px;
            border-radius: 4px;
            margin-top: 2px;
            background: #EFE8E1;
            color: #7A5530;
        }
    </style>
</head>

<body>
    @php($adminUser = auth()->user())
    <div class="admin-portal">
        <button class="admin-sidebar-backdrop" type="button" data-close-admin-sidebar
            aria-label="Close navigation"></button>
        <aside class="admin-sidebar" data-admin-sidebar>
            <div class="admin-sidebar-top">
                <a class="brand" href="{{ route('admin.dashboard', [], false) }}"><span
                        class="brand-mark"><i></i><i></i></span>MOA</a>
                <button class="sidebar-close" type="button" data-close-admin-sidebar
                    aria-label="Close navigation">×</button>
            </div>
            <a class="admin-profile-chip" href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;" title="Manage your administrator profile">
                <span class="patient-avatar"
                    style="background: #9F652C; color: #fff;">{{ strtoupper(substr($adminUser->name, 0, 1)) }}</span>
                <span>
                    <strong>{{ $adminUser->name }}</strong>
                    <span class="staff-role-badge">{{ ucfirst($adminUser->role) }}</span>
                </span>
            </a>
            <nav class="patient-nav" aria-label="Clinic navigation">
                <span class="patient-nav-label">Executive & Desk</span>
                <a class="patient-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                    href="{{ route('admin.dashboard') }}">
                    <span class="nav-icon"><i class="bx bx-home"></i></span>Overview & KPIs
                </a>
                <a class="patient-nav-link {{ request()->routeIs('admin.queue') ? 'active' : '' }}"
                    href="{{ route('admin.queue') }}">
                    <span class="nav-icon"><i class="bx bx-list-ol"></i></span>Front-Desk & Queue
                </a>
                <a class="patient-nav-link {{ request()->routeIs('admin.live-queue') ? 'active' : '' }}"
                    href="{{ route('admin.live-queue') }}" target="_blank">
                    <span class="nav-icon"><i class="bx bx-calendar-plus"></i></span>Waiting Room Display
                </a>

                <span class="patient-nav-label patient-nav-label-spaced">Clinical Modules</span>
                <a class="patient-nav-link {{ request()->routeIs('admin.clinical') ? 'active' : '' }}"
                    href="{{ route('admin.clinical') }}">
                    <span class="nav-icon"><i class="bx bx-pulse"></i></span>EHR & Refraction
                </a>
                <a class="patient-nav-link {{ request()->routeIs('admin.scheduling') ? 'active' : '' }}"
                    href="{{ route('admin.scheduling') }}">
                    <span class="nav-icon"><i class="bx bx-calendar-event"></i></span>Master Scheduling
                </a>

                <span class="patient-nav-label patient-nav-label-spaced">Optical & Financial</span>
                <a class="patient-nav-link {{ request()->routeIs('admin.dispensing') ? 'active' : '' }}"
                    href="{{ route('admin.dispensing') }}">
                    <span class="nav-icon"><i class="bx bx-cart"></i></span>Dispensing & POS
                </a>
                <a class="patient-nav-link {{ request()->routeIs('admin.lab-orders') ? 'active' : '' }}"
                    href="{{ route('admin.lab-orders') }}">
                    <span class="nav-icon"><i class="bx bx-cog"></i></span>Lab Order Pipeline
                </a>
                <a class="patient-nav-link {{ request()->routeIs('admin.billing') ? 'active' : '' }}"
                    href="{{ route('admin.billing') }}">
                    <span class="nav-icon"><i class="bx bx-credit-card"></i></span>Billing & HMO
                </a>

                @if($adminUser->isAdmin())
                <span class="patient-nav-label patient-nav-label-spaced">Governance</span>
                <a class="patient-nav-link {{ request()->routeIs('admin.staff.*') ? 'active' : '' }}"
                    href="{{ route('admin.staff.index') }}">
                    <span class="nav-icon"><i class="bx bx-shield-quarter"></i></span>Staff & Admins
                </a>
                @endif

                <span class="patient-nav-label patient-nav-label-spaced">Records</span>
                <a class="patient-nav-link" href="{{ route('admin.dashboard') }}#patients">
                    <span class="nav-icon"><i class="bx bx-user"></i></span>Patients
                </a>
                <a class="patient-nav-link" href="{{ route('admin.dashboard') }}#prescriptions">
                    <span class="nav-icon"><i class="bx bx-file-blank"></i></span>Prescriptions
                </a>
                <a class="patient-nav-link" href="{{ route('admin.dashboard') }}#inventory">
                    <span class="nav-icon"><i class="bx bx-package"></i></span>Inventory Alerts
                </a>
                <a class="patient-nav-link" href="{{ route('admin.dashboard') }}#audit">
                    <span class="nav-icon"><i class="bx bx-history"></i></span>Audit Logs
                </a>
            </nav>
            <div class="patient-sidebar-footer">
                <a class="patient-nav-link {{ request()->routeIs('admin.profile') ? 'active' : '' }}"
                    href="{{ route('admin.profile') }}">
                    <span class="nav-icon"><i class="bx bx-user-circle"></i></span>My Profile
                </a>
                <button class="patient-nav-link patient-logout-link" type="button" data-open-admin-logout>
                    <span class="nav-icon"><i class="bx bx-log-out"></i></span>Log out
                </button>
            </div>
        </aside>
        <main class="admin-content">
            <header class="patient-mobile-header">
                <button class="sidebar-toggle" type="button" data-open-admin-sidebar aria-label="Open navigation"><i
                        class="bx bx-menu"></i></button>
                <a class="brand" href="{{ route('admin.dashboard', [], false) }}"><span
                        class="brand-mark"><i></i><i></i></span>MOA</a>
                <span class="patient-mobile-spacer"></span>
            </header>
            @yield('content')
        </main>
    </div>
    <div class="modal-backdrop logout-modal-backdrop" data-admin-logout-modal hidden>
        <section class="modal logout-modal" role="dialog" aria-modal="true" aria-labelledby="admin-logout-title"
            aria-describedby="admin-logout-description">
            <button class="modal-close" type="button" data-close-admin-logout
                aria-label="Close logout dialog">×</button>
            <h2 id="admin-logout-title">Leave the portal?</h2>
            <p id="admin-logout-description">You will need to sign in again to manage clinic operations.</p>
            <div class="logout-actions"><button class="button button-quiet" type="button" data-close-admin-logout>Stay
                    signed in</button>
                <form method="POST" action="{{ route('auth.logout', [], false) }}">@csrf<button
                        class="button button-dark" type="submit">Log out</button></form>
            </div>
        </section>
    </div>
    <script>
        const adminSidebar = document.querySelector('[data-admin-sidebar]'); const openAdminSidebar = () => { adminSidebar?.classList.add('is-open'); document.body.classList.add('admin-sidebar-open'); }; const closeAdminSidebar = () => { adminSidebar?.classList.remove('is-open'); document.body.classList.remove('admin-sidebar-open'); }; document.querySelector('[data-open-admin-sidebar]')?.addEventListener('click', openAdminSidebar); document.querySelectorAll('[data-close-admin-sidebar]').forEach((button) => button.addEventListener('click', closeAdminSidebar));
        const adminLogoutModal = document.querySelector('[data-admin-logout-modal]'); const adminLogoutTrigger = document.querySelector('[data-open-admin-logout]'); const closeAdminLogout = () => { adminLogoutModal.hidden = true; adminLogoutTrigger?.focus(); }; adminLogoutTrigger?.addEventListener('click', () => { adminLogoutModal.hidden = false; adminLogoutModal.querySelector('[data-close-admin-logout]')?.focus(); }); adminLogoutModal?.querySelectorAll('[data-close-admin-logout]').forEach((button) => button.addEventListener('click', closeAdminLogout)); adminLogoutModal?.addEventListener('click', (event) => { if (event.target === adminLogoutModal) closeAdminLogout(); }); document.addEventListener('keydown', (event) => { if (event.key === 'Escape') { if (adminLogoutModal && !adminLogoutModal.hidden) closeAdminLogout(); else closeAdminSidebar(); } });
    </script>
    @stack('modals')
    @stack('scripts')
</body>

</html>
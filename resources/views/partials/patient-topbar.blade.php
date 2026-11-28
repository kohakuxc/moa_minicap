@php($user = auth()->user())
<header class="site-header patient-topbar" data-topbar>
    <a class="brand" href="/" aria-label="MOA home"><span class="brand-mark"><i></i><i></i></span><span>MOA</span></a>
    <nav class="main-nav patient-topnav" id="patient-navigation" aria-label="Main navigation">
        <a class="{{ request()->is('/#home') ? 'active' : '' }}" href="/#home">Home</a>
        <a class="{{ request()->routeIs('patient.eyewear') ? 'active' : '' }}"
            href="{{ route('patient.eyewear', [], false) }}">Catalog</a>
        <a class="{{ request()->routeIs('patient.care-services') ? 'active' : '' }}"
            href="{{ route('patient.care-services', [], false) }}">Services</a>
        <a href="/#about">Our story</a>
        <details
            class="account-menu {{ request()->routeIs('dashboard', 'patient.appointments.*', 'patient.prescriptions', 'patient.profile') ? 'active' : '' }}"
            data-account-menu>
            <summary>My Account <i class="bx bx-chevron-down"></i></summary>
            <div class="account-menu-panel">
                <div class="account-menu-user"><span
                        class="patient-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span><span><strong>{{ $user->name }}</strong><small>Patient
                            account</small></span></div>
                <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    href="{{ route('dashboard', [], false) }}">Dashboard</a>
                <a class="{{ request()->routeIs('patient.appointments.create') ? 'active' : '' }}"
                    href="{{ route('patient.appointments.create', [], false) }}">Book appointment</a>
                <a class="{{ request()->routeIs('patient.appointments.upcoming') ? 'active' : '' }}"
                    href="{{ route('patient.appointments.upcoming', [], false) }}">Scheduled appointments</a>
                <a class="{{ request()->routeIs('patient.prescriptions') ? 'active' : '' }}"
                    href="{{ route('patient.prescriptions', [], false) }}">Prescriptions</a>
                <a class="{{ request()->routeIs('patient.appointments.history') ? 'active' : '' }}"
                    href="{{ route('patient.appointments.history', [], false) }}">Visit history</a>
                <a class="{{ request()->routeIs('patient.profile') ? 'active' : '' }}"
                    href="{{ route('patient.profile', [], false) }}">My profile</a>
                <button type="button" data-open-logout>Log out</button>
            </div>
        </details>
    </nav>
    @if(request()->is('/'))
        <div class="header-actions">
            <a class="button button-teal button-small" href="{{ route('patient.appointments.create', [], false) }}">Book an
                appointment</a>
        </div>
    @endif
    <button class="menu-button" type="button" data-menu-toggle aria-expanded="false" aria-controls="patient-navigation"
        aria-label="Open menu"><span class="menu-icon" aria-hidden="true"><i></i><i></i><i></i></span></button>
</header>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Patient dashboard · MOA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alegreya+SC:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/app.css">
</head>

<body>
    <main class="dashboard-page">
        <header class="dashboard-header">
            <a class="brand" href="{{ route('dashboard', [], false) }}"><span
                    class="brand-mark"><i></i><i></i></span>MOA</a>
            <nav class="dashboard-nav" aria-label="Account navigation">
                <a class="text-button" href="{{ route('patient.profile', [], false) }}">My profile</a>
                <button class="text-button" type="button" data-open-logout>Log out</button>
            </nav>
        </header>
        <section class="dashboard-intro">
            <div>
                <h1>Good to see you, <em>{{ $user->name }}</em></h1>
                <p>Manage your appointments, eyewear preferences, and care history in one place.</p>
            </div>
            @if(session('success'))
            <p class="success-message">{{ session('success') }}</p>@endif
        </section>
        <div class="dashboard-grid">
            <section class="dashboard-card appointment-card">
                <div class="card-heading"><span class="module-number">01</span>
                    <div>
                        <div class="eyebrow">BOOKING</div>
                        <h2>Request an appointment</h2>
                    </div>
                </div>
                <form method="POST" action="{{ route('appointments.store', [], false) }}">
                    @csrf
                    <label>What do you need? <span>*</span><select name="service" required>
                            <option value="Comprehensive eye exam">Eye exam</option>
                            <option value="Special case examination">Special case examination</option>
                            <option value="Frame styling">Frame styling</option>
                        </select></label>
                    <div class="form-row"><label>Date<input type="date" name="appointment_date"
                                min="{{ now()->toDateString() }}" required data-slot-date></label><label>Available
                            time<select name="starts_at" required data-slot-select disabled>
                                <option>Select a date first</option>
                            </select></label></div>
                    <label>Notes<textarea name="notes" rows="3"
                            placeholder="Tell us anything we should know"></textarea></label>
                    <button class="button button-teal" type="submit">Request appointment <i class="bx bx-right-arrow-alt"></i></button>
                </form>
                <p class="auth-error" data-slot-error hidden></p>
            </section>
            <section class="dashboard-card eyewear-card">
                <div class="card-heading"><span class="module-number">02</span>
                    <div>
                        <div class="eyebrow">PREFERENCES</div>
                        <h2>Choose your eyewear</h2>
                    </div>
                </div>
                <p class="card-description">Select what you are looking for.</p>
                <div class="choice-grid"><button type="button"
                        class="choice selected"><b><i class="bx bx-sun"></i></b><span><strong>Sunglasses</strong><small>Protection with
                                signature style</small></span></button><button type="button"
                        class="choice"><b><i class="bx bx-book-open"></i></b><span><strong>Reading glasses</strong><small>Comfort for close-up
                                vision</small></span></button><button type="button"
                        class="choice"><b><i class="bx bx-glasses"></i></b><span><strong>Prescription glasses</strong><small>Graded
                                lenses</small></span></button></div>
            </section>
            <section class="dashboard-card service-card">
                <div class="card-heading"><span class="module-number">03</span>
                    <div>
                        <div class="eyebrow">CARE SERVICES</div>
                        <h2>Check up with the doctor</h2>
                    </div>
                </div>
                <div class="service-list">
                    <div><span>01</span><strong>Comprehensive eye exam</strong><small>Routine vision and eye health
                            check</small></div>
                    <div><span>02</span><strong>Special case examination</strong><small>For specific concerns or
                            follow-ups</small></div>
                </div>
            </section>
            <section class="dashboard-card">
                <h2>Scheduled appointments</h2>@forelse($upcomingAppointments as $appointment)
                    <div class="record-row">
                        <div><strong>{{ $appointment->service }}</strong><small>{{ $appointment->starts_at->format('M j, Y · g:i A') }}
                                ·
                                {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</small>@if($appointment->reason)<small>Reason:
                                {{ $appointment->reason }}</small>@endif</div>
                        @if(!in_array($appointment->status, ['cancelled', 'rejected']))
                            <form method="POST" action="{{ route('appointments.cancel', $appointment, false) }}">@csrf
                                @method('DELETE')<input name="reason" required placeholder="Reason"
                                    aria-label="Cancellation reason"><button class="text-button" type="submit">Cancel</button>
                        </form>@endif
                </div>@empty<p class="empty-state">No scheduled appointments.</p>@endforelse
            </section>
            <section class="dashboard-card">
                <h2>Prescription history</h2>@forelse($prescriptions as $prescription)
                    <div class="record-row">
                        <div><strong>{{ $prescription->eye }} · Sphere
                                {{ $prescription->sphere ?? '—' }}</strong><small>Issued
                                {{ $prescription->issued_at->format('M j, Y') }} · Cylinder
                                {{ $prescription->cylinder ?? '—' }} · Axis {{ $prescription->axis ?? '—' }}</small></div>
                </div>@empty<p class="empty-state">No prescriptions on file.</p>@endforelse
            </section>
            <section class="dashboard-card">
                <h2>Previous visits</h2>@forelse($pastAppointments as $appointment)
                    <div class="record-row">
                        <div><strong>{{ $appointment->service }}</strong><small>{{ $appointment->starts_at->format('M j, Y') }}
                                · {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</small></div>
                </div>@empty<p class="empty-state">No previous visits.</p>@endforelse
            </section>
        </div>
    </main>
    <div class="modal-backdrop logout-modal-backdrop" data-logout-modal hidden>
        <section class="modal logout-modal" role="dialog" aria-modal="true" aria-labelledby="logout-title"
            aria-describedby="logout-description">
            <button class="modal-close" type="button" data-close-logout aria-label="Close logout dialog">×</button>
            <h2 id="logout-title">Leave the portal?</h2>
            <p id="logout-description">You will need to sign in again to manage your appointments and care history.</p>
            <div class="logout-actions">
                <button class="button button-quiet" type="button" data-close-logout>Stay signed in</button>
                <form method="POST" action="{{ route('auth.logout', [], false) }}">
                    @csrf
                    <button class="button button-dark" type="submit">Log out</button>
                </form>
            </div>
        </section>
    </div>
    <script>document.querySelector('[data-slot-date]')?.addEventListener('change', async (event) => { const select = document.querySelector('[data-slot-select]'), error = document.querySelector('[data-slot-error]'); select.disabled = true; select.innerHTML = '<option>Loading times...</option>'; error.hidden = true; try { const response = await fetch('{{ route('appointments.slots', [], false) }}?date=' + encodeURIComponent(event.target.value), { headers: { Accept: 'application/json' } }); const data = await response.json(); if (!response.ok) throw new Error(data.message || 'Unable to load times.'); select.innerHTML = data.data.length ? '<option value="">Choose a time</option>' + data.data.map(slot => `<option value="${slot.value}">${slot.label}</option>`).join('') : '<option>No times available</option>'; select.disabled = !data.data.length; } catch (errorValue) { select.innerHTML = '<option>Unable to load times</option>'; error.textContent = errorValue.message; error.hidden = false; } });</script>
    <script>const logoutModal = document.querySelector('[data-logout-modal]'); const logoutTrigger = document.querySelector('[data-open-logout]'); const closeLogout = () => { logoutModal.hidden = true; logoutTrigger?.focus(); }; logoutTrigger?.addEventListener('click', () => { logoutModal.hidden = false; logoutModal.querySelector('[data-close-logout]')?.focus(); }); logoutModal?.querySelectorAll('[data-close-logout]').forEach((button) => button.addEventListener('click', closeLogout)); logoutModal?.addEventListener('click', (event) => { if (event.target === logoutModal) closeLogout(); }); document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && logoutModal && !logoutModal.hidden) closeLogout(); });</script>
</body>

</html>
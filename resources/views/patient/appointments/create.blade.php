@extends('patient.layout')

@section('title', 'Book an appointment')

@section('content')
    <div class="patient-page-head">
        <div>
            <h1>Choose a time for <em>your care.</em></h1>
            <p>Pick a service and a time that suits you. We will confirm the details with you.</p>
        </div>
    </div>
    @if(session('success'))
    <p class="portal-feedback" role="status">{{ session('success') }}</p>@endif
    <div class="split-page-grid">
        <section class="portal-panel booking-panel">
            <form method="POST" action="{{ route('appointments.store', [], false) }}" data-booking-form>@csrf<label>Service
                    <span>*</span><select name="service" required>
                        <option value="Comprehensive eye exam">Eye exam</option>
                        <option value="Special case examination">Special case examination</option>
                        <option value="Frame styling">Frame styling</option>
                    </select></label>
                <div class="form-row"><label>Date<input type="date" name="appointment_date"
                            min="{{ now()->toDateString() }}" required data-slot-date></label><label>Available time<select
                            name="starts_at" required data-slot-select disabled>
                            <option>Select a date first</option>
                        </select></label></div><label>Notes<textarea name="notes" rows="5"
                        placeholder="Tell us anything we should know"></textarea></label><button
                    class="button button-teal button-primary" type="submit" data-submit>Request appointment
                    <span>→</span></button>
            </form>
            <p class="auth-error" data-slot-error hidden role="alert"></p>
        </section>
        <aside class="portal-note">
            <div class="eyebrow">BEFORE YOUR VISIT</div>
            <h2>Good care starts with a good conversation.</h2>
            <p>Bring your current prescription and let us know about any concerns before your visit.</p>
        </aside>
    </div>
@endsection

@push('scripts')
    <script>const bookingForm = document.querySelector('[data-booking-form]'), submitButton = document.querySelector('[data-submit]'); document.querySelector('[data-slot-date]')?.addEventListener('change', async (event) => { const select = document.querySelector('[data-slot-select]'), error = document.querySelector('[data-slot-error]'); select.disabled = true; select.setAttribute('aria-busy', 'true'); select.innerHTML = '<option>Loading available times…</option>'; error.hidden = true; try { const response = await fetch('{{ route('appointments.slots', [], false) }}?date=' + encodeURIComponent(event.target.value), { headers: { Accept: 'application/json' } }); const data = await response.json(); if (!response.ok) throw new Error(data.message || 'Unable to load times.'); select.innerHTML = data.data.length ? '<option value="">Choose a time</option>' + data.data.map(slot => `<option value="${slot.value}">${slot.label}</option>`).join('') : '<option>No times available for this date</option>'; select.disabled = !data.data.length; select.removeAttribute('aria-busy'); if (!data.data.length) { error.textContent = 'There are no available times on this date. Please choose another.'; error.hidden = false; } } catch (errorValue) { select.innerHTML = '<option>Unable to load times</option>'; select.removeAttribute('aria-busy'); error.textContent = errorValue.message; error.hidden = false; } }); bookingForm?.addEventListener('submit', () => { submitButton.disabled = true; submitButton.setAttribute('aria-busy', 'true'); submitButton.firstChild.textContent = 'Sending request…'; });</script>
@endpush
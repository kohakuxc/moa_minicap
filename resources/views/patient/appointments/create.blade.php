@extends('patient.layout')

@section('title', 'Smart Online Booking & Digital Intake')

@section('content')
    <div class="patient-page-head">
        <div>
            <h1>Smart online booking & <em>pre-visit intake.</em></h1>
            <p>Select your specialized optical service, choose your preferred optometrist, and complete your digital health intake ahead of your visit.</p>
        </div>
    </div>

    @if(session('success'))
        <p class="portal-feedback" role="status" style="border-left: 4px solid var(--moa-teal); background: rgba(100, 188, 180, 0.12); color: #164e63;">
            <i class="bx bx-check-circle"></i> {{ session('success') }}
        </p>
    @endif

    @if($errors->any())
        <div class="admin-validation" role="alert" style="margin-bottom: 20px;">
            <strong>Check your booking details:</strong>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    @if($activeAppointment)
        <div class="portal-feedback" role="note" style="border-left: 4px solid var(--moa-bronze, #b7772f); background: rgba(240, 186, 127, 0.18); color: #6b4a1f; margin-bottom: 20px;">
            <strong><i class="bx bx-info-circle"></i> You already have an appointment.</strong><br>
            {{ $activeAppointment->service }} on {{ $activeAppointment->starts_at->format('l, F j, Y \a\t g:i A') }}.
            {{ \App\Support\BookingRules::oneAppointmentPolicy() }}
            If you continue, you'll be asked to confirm and give a reason for cancelling it.
        </div>
    @endif

    <div class="split-page-grid" style="grid-template-columns: 2fr 1.2fr; gap: 32px;">
        <section class="portal-panel booking-panel" style="padding: 28px;">
            <form method="POST" action="{{ route('appointments.store', [], false) }}" data-booking-form style="display: flex; flex-direction: column; gap: 20px;">
                @csrf
                <input type="hidden" name="replace_existing" value="0" data-replace-flag>
                <input type="hidden" name="cancellation_reason" value="" data-replace-reason-field>
                <!-- Section 1: Service & Specialist -->
                <div>
                    <div style="font-weight: 700; font-size: 14px; color: var(--moa-bronze); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        <span>1.</span> SELECT CLINICAL SERVICE & SPECIALIST
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <label style="display: flex; flex-direction: column; gap: 5px; font-weight: 600; font-size: 13px;">
                             <span class="required">Optical Service</span>
                            <select name="service_id" id="service_select" required style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 14px;">
                                @foreach($services as $svc)
                                    <option value="{{ $svc->id }}" data-duration="{{ $svc->duration_minutes }}" data-fee="{{ $svc->fee }}" {{ (string)old('service_id', request('service_id')) === (string)$svc->id ? 'selected' : '' }}>
                                        {{ $svc->name }} ({{ $svc->duration_minutes }} min · ₱{{ number_format($svc->fee, 2) }})
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label style="display: flex; flex-direction: column; gap: 5px; font-weight: 600; font-size: 13px;">
                            Doctor / Optometrist
                            <select name="doctor_id" id="doctor_select" style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 14px;">
                                <option value="">First Available Doctor / Optometrist</option>
                                @foreach($doctors as $doc)
                                    <option value="{{ $doc->id }}">{{ $doc->name }} ({{ ucfirst($doc->role) }})</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>

                <!-- Section 2: Date & Available Slot -->
                <div style="border-top: 1px solid #EAE4DC; padding-top: 18px;">
                    <div style="font-weight: 700; font-size: 14px; color: var(--moa-bronze); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        <span>2.</span> CHOOSE DATE & REAL-TIME SLOT
                    </div>

                    @php($bookingDays = \App\Support\BookingRules::days())
                    <style>
                        .day-picker { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 8px; }
                        .day-card { display: flex; flex-direction: column; align-items: center; gap: 2px; padding: 10px 4px; border: 1px solid #D5CBC0; border-radius: 8px; background: #fff; cursor: pointer; font: inherit; color: inherit; }
                        .day-card b { font-size: 11px; font-weight: 600; text-transform: uppercase; color: #7a7064; }
                        .day-card strong { font-size: 18px; }
                        .day-card small { font-size: 11px; color: #7a7064; }
                        .day-card:hover:not(.unavailable) { border-color: var(--moa-teal, #3b6bd6); }
                        .day-card.selected { background: var(--moa-teal, #3b6bd6); border-color: var(--moa-teal, #3b6bd6); color: #fff; }
                        .day-card.selected b, .day-card.selected small { color: #fff; }
                        .day-card.unavailable { background: #f3efe9; opacity: .6; }
                        .day-card.unavailable.focused { opacity: 1; border-color: #C53030; }
                        @media (max-width: 640px) { .day-picker { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
                    </style>
                    <div style="display: flex; flex-direction: column; gap: 6px; font-weight: 600; font-size: 13px; margin-bottom: 14px;">
                        <span class="required">Date</span>
                        <small style="font-weight: 400; color: #7a7064;">Appointments open {{ \App\Support\BookingRules::LEAD_DAYS }} days from today, for a {{ \App\Support\BookingRules::WINDOW_DAYS }}-day window, with a maximum of {{ \App\Support\BookingRules::DAILY_LIMIT }} appointments per day. Tap any date to see its availability.</small>
                        <div class="day-picker" role="group" aria-label="Choose an appointment date">
                            @foreach($bookingDays as $day)
                                <button type="button" class="day-card {{ $day['available'] ? '' : 'unavailable' }}" data-day="{{ $day['date'] }}" data-reason="{{ $day['reason'] }}">
                                    <b>{{ $day['weekday'] }}</b><strong>{{ $day['day'] }}</strong><small>{{ $day['month'] }}</small>
                                </button>
                            @endforeach
                        </div>
                        <input type="hidden" name="appointment_date" data-slot-date>
                    </div>

                    <label style="display: flex; flex-direction: column; gap: 5px; font-weight: 600; font-size: 13px;">
                        <span class="required">Available Time</span>
                        <select name="starts_at" required data-slot-select disabled style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px;">
                            <option>Select a date first</option>
                        </select>
                    </label>
                    <p class="auth-error" data-slot-error hidden role="alert" style="color: #C53030; font-size: 13px; margin-top: 8px;"></p>
                </div>

                <!-- Section 3: Digital Pre-Visit Health Intake -->
                <div style="border-top: 1px solid #EAE4DC; padding-top: 18px; background: #FAF7F2; padding: 18px; border-radius: 8px;">
                    <div style="font-weight: 700; font-size: 14px; color: var(--moa-teal); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                        <span>3.</span> PRE-VISIT DIGITAL INTAKE (OPTIONAL BUT RECOMMENDED)
                    </div>
                    <p style="font-size: 12px; color: var(--moa-slate); margin: 0 0 14px;">
                        Helps your optometrist prepare necessary diagnostic equipment and verify insurance allowances ahead of time.
                    </p>

                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                            Current Vision Symptoms / Chief Concerns
                            <input type="text" name="symptoms" placeholder="e.g. Blurriness when reading, eye strain at computer, headaches" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
                        </label>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                                Medical / General Health History
                                <input type="text" name="medical_history" placeholder="e.g. Hypertension, Diabetes, None" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
                            </label>
                            <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                                Known Allergies / Eye Sensitivities
                                <input type="text" name="allergies" placeholder="e.g. Latex, preservative drops, None" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
                            </label>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                                HMO Insurance Provider (if applicable)
                                <select name="hmo_provider_name" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
                                    <option value="">-- No HMO / Direct Settlement --</option>
                                    @foreach($hmoProviders as $hmo)
                                        <option value="{{ $hmo->name }}">{{ $hmo->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                                HMO Card / Member Number
                                <input type="text" name="hmo_member_id" placeholder="e.g. MC-981240" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
                            </label>
                        </div>

                        <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                            Preferred Eyewear Interest
                            <select name="preferred_eyewear" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
                                <option value="prescription_glasses">Prescription Eyeglasses & Blue Light Protection</option>
                                <option value="contact_lenses">Soft / Toric / Multifocal Contact Lenses</option>
                                <option value="sunglasses">Prescription Sunglasses</option>
                                <option value="consultation_only">Clinical Eye Health Examination Only</option>
                            </select>
                        </label>
                    </div>
                </div>

                <label style="display: flex; flex-direction: column; gap: 5px; font-weight: 600; font-size: 13px;">
                    Additional Notes for Reception Desk
                    <textarea name="notes" rows="3" placeholder="Tell us anything else we should know ahead of your visit" style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-family: inherit; font-size: 13px;"></textarea>
                </label>

                <button class="button button-teal button-primary" type="submit" data-submit style="padding: 12px 24px; font-size: 14px;">
                    Confirm & Submit Booking Request
                </button>
            </form>
        </section>

        <aside class="portal-note" style="padding: 24px; background: #fff; border: 1px solid #E5DFD7; border-radius: 12px; height: fit-content;">
            <h2 style="margin: 8px 0 12px; font-size: 20px;">What to bring on your visit.</h2>
            <ul style="padding-left: 18px; font-size: 13px; color: #4A4540; line-height: 1.6; margin: 0 0 18px;">
                <li>Your current spectacles or contact lens packaging.</li>
                <li>HMO / Insurance physical card or active digital LOA.</li>
                <li>List of current systemic eye drops or oral medications.</li>
                <li>If having retinal pupil dilation, sunglasses for light sensitivity afterwards.</li>
            </ul>

            <div style="background: rgba(240, 186, 127, 0.18); border-radius: 8px; padding: 14px; font-size: 12px; color: var(--moa-bronze);">
                <strong>Fast-Track Front Desk:</strong> Completing your digital pre-visit intake above automatically generates your queue ticket upon arrival!
            </div>
        </aside>
    </div>
@endsection

@if($activeAppointment)
@push('modals')
<div class="modal-backdrop logout-modal-backdrop" data-replace-modal hidden>
    <section class="modal logout-modal" role="dialog" aria-modal="true" aria-labelledby="replace-title" style="max-width: 520px;">
        <button class="modal-close" type="button" data-replace-close aria-label="Close">×</button>
        <h2 id="replace-title">One appointment per account</h2>
        <p>{{ \App\Support\BookingRules::oneAppointmentPolicy() }}</p>
        <p style="background: #f6f1ea; border-radius: 8px; padding: 10px 12px; font-size: 14px;">
            <strong>Your current appointment:</strong><br>
            {{ $activeAppointment->service }} · {{ $activeAppointment->starts_at->format('l, F j, Y \a\t g:i A') }}
        </p>
        <label style="display: flex; flex-direction: column; gap: 5px; font-weight: 600; font-size: 13px; text-align: left;">
            Reason for cancelling your current appointment
            <textarea rows="3" maxlength="500" data-replace-reason placeholder="e.g. I need a different date" style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font: inherit;"></textarea>
        </label>
        <label style="display: flex; gap: 8px; align-items: flex-start; font-size: 13px; margin-top: 12px; text-align: left;">
            <input type="checkbox" data-replace-agree style="margin-top: 3px;">
            <span>I understand the policy and agree to cancel my previous appointment in order to book this new one.</span>
        </label>
        <p class="auth-error" data-replace-error hidden role="alert" style="color: #C53030; font-size: 13px; margin: 10px 0 0;"></p>
        <div class="logout-actions">
            <button class="button button-quiet" type="button" data-replace-close>Keep my current appointment</button>
            <button class="button button-dark" type="button" data-replace-confirm>Cancel previous &amp; book new</button>
        </div>
    </section>
</div>
@endpush
@endif
@push('scripts')
<script>
const bookingForm = document.querySelector('[data-booking-form]');
const submitButton = document.querySelector('[data-submit]');
const dateInput = document.querySelector('[data-slot-date]');
const doctorSelect = document.getElementById('doctor_select');

async function loadSlots() {
    const dateVal = dateInput?.value;
    if (!dateVal) return;

    const select = document.querySelector('[data-slot-select]');
    const error = document.querySelector('[data-slot-error]');
    select.disabled = true;
    select.setAttribute('aria-busy', 'true');
    select.innerHTML = '<option>Loading available times…</option>';
    error.hidden = true;

    try {
        let url = '{{ route('appointments.slots', [], false) }}?date=' + encodeURIComponent(dateVal);
        if (doctorSelect?.value) {
            url += '&doctor_id=' + encodeURIComponent(doctorSelect.value);
        }

        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Unable to load times.');

        select.innerHTML = data.data.length
            ? '<option value="">Choose a time slot</option>' + data.data.map(slot => `<option value="${slot.value}">${slot.label}</option>`).join('')
            : '<option>No times available for this date</option>';

        select.disabled = !data.data.length;
        select.removeAttribute('aria-busy');

        if (!data.data.length) {
            error.textContent = data.reason || 'There are no open times on this date. Please choose another date.';
            error.hidden = false;
        }
    } catch (errorValue) {
        select.innerHTML = '<option>Unable to load times</option>';
        select.removeAttribute('aria-busy');
        error.textContent = errorValue.message;
        error.hidden = false;
    }
}

document.querySelectorAll('.day-card').forEach((card) => card.addEventListener('click', () => {
    const select = document.querySelector('[data-slot-select]');
    const error = document.querySelector('[data-slot-error]');
    document.querySelectorAll('.day-card').forEach((item) => item.classList.remove('selected', 'focused'));

    if (card.dataset.reason) {
        // Blocked date: explain why instead of selecting it.
        card.classList.add('focused');
        dateInput.value = '';
        select.innerHTML = '<option>Select an available date first</option>';
        select.disabled = true;
        error.textContent = card.dataset.reason;
        error.hidden = false;
        return;
    }

    card.classList.add('selected');
    dateInput.value = card.dataset.day;
    loadSlots();
}));
doctorSelect?.addEventListener('change', loadSlots);

bookingForm?.addEventListener('submit', (event) => {
    const flag = document.querySelector('[data-replace-flag]');
    const modal = document.querySelector('[data-replace-modal]');
    if (modal && flag.value !== '1') {
        // Existing appointment: show the one-appointment policy and ask for agreement + reason first.
        event.preventDefault();
        modal.hidden = false;
        modal.querySelector('[data-replace-reason]')?.focus();
        return;
    }
    submitButton.disabled = true;
    submitButton.setAttribute('aria-busy', 'true');
    submitButton.firstChild.textContent = '';
});

(() => {
    const modal = document.querySelector('[data-replace-modal]');
    if (!modal) return;
    const error = modal.querySelector('[data-replace-error]');
    const close = () => { modal.hidden = true; error.hidden = true; };
    modal.querySelectorAll('[data-replace-close]').forEach((btn) => btn.addEventListener('click', close));
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    modal.querySelector('[data-replace-confirm]').addEventListener('click', () => {
        const reason = modal.querySelector('[data-replace-reason]').value.trim();
        if (!modal.querySelector('[data-replace-agree]').checked) {
            error.textContent = 'Please tick the box to confirm you agree to cancel your previous appointment.';
            error.hidden = false;
            return;
        }
        if (reason.length < {{ \App\Support\BookingRules::CANCELLATION_REASON_MIN }}) {
            error.textContent = 'Please give a reason for cancelling (at least {{ \App\Support\BookingRules::CANCELLATION_REASON_MIN }} characters).';
            error.hidden = false;
            return;
        }
        document.querySelector('[data-replace-flag]').value = '1';
        document.querySelector('[data-replace-reason-field]').value = reason;
        modal.hidden = true;
        bookingForm.requestSubmit(submitButton);
    });
    @if(session('existing_appointment'))
        modal.hidden = false;
    @endif
})();
</script>
@endpush

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

    <div class="split-page-grid" style="grid-template-columns: 2fr 1.2fr; gap: 32px;">
        <section class="portal-panel booking-panel" style="padding: 28px;">
            <form method="POST" action="{{ route('appointments.store', [], false) }}" data-booking-form style="display: flex; flex-direction: column; gap: 20px;">
                @csrf

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

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <label style="display: flex; flex-direction: column; gap: 5px; font-weight: 600; font-size: 13px;">
                             <span class="required">Date</span>
                            <input type="date" name="appointment_date" min="{{ now()->toDateString() }}" required data-slot-date style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px;">
                        </label>

                        <label style="display: flex; flex-direction: column; gap: 5px; font-weight: 600; font-size: 13px;">
                             <span class="required">Available Time</span>
                            <select name="starts_at" required data-slot-select disabled style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px;">
                                <option>Select a date first</option>
                            </select>
                        </label>
                    </div>
                    <p class="auth-error" data-slot-error hidden role="alert" style="color: #C53030; font-size: 12px; margin-top: 6px;"></p>
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
            error.textContent = 'There are no open doctor times on this date. Please choose another weekday.';
            error.hidden = false;
        }
    } catch (errorValue) {
        select.innerHTML = '<option>Unable to load times</option>';
        select.removeAttribute('aria-busy');
        error.textContent = errorValue.message;
        error.hidden = false;
    }
}

dateInput?.addEventListener('change', loadSlots);
doctorSelect?.addEventListener('change', loadSlots);

bookingForm?.addEventListener('submit', () => {
    submitButton.disabled = true;
    submitButton.setAttribute('aria-busy', 'true');
    submitButton.firstChild.textContent = 'Submitting intake & booking…';
});
</script>
@endpush

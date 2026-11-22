@extends('admin.layout')

@section('title', 'EHR & Digital Refraction Builder')

@section('content')
<div class="admin-page-head">
    <div>
        <h1>EHR clinical examination & <em>refraction builder.</em></h1>
        <p>Comprehensive patient visual assessment, binocular refraction, tonometry, fundus observations, and tamper-evident digital prescriptions.</p>
    </div>
</div>

@if(session('success'))
    <p class="portal-feedback" role="status" style="border-left: 4px solid var(--moa-teal); background: rgba(100, 188, 180, 0.12); color: #164e63;">
        <i class="bx bx-check-circle"></i> {{ session('success') }}
    </p>
@endif

@if($errors->any())
    <div class="admin-validation" role="alert">
        <strong>Please check clinical inputs:</strong>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<!-- Queue Patient Quick Picker -->
@if($queuePatients->isNotEmpty())
<div style="background: rgba(240, 186, 127, 0.18); border: 1px solid var(--moa-gold); border-radius: 10px; padding: 14px 18px; margin-bottom: 22px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div>
        <strong style="color: var(--moa-bronze);">Patients In Queue Awaiting Exam:</strong>
        <span style="font-size: 13px; color: #555; margin-left: 6px;">Select to load patient chart into the clinical builder:</span>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        @foreach($queuePatients as $qp)
        <a href="{{ route('admin.clinical', ['patient_id' => $qp->patient_id, 'appointment_id' => $qp->appointment_id]) }}" class="button button-quiet" style="font-size: 12px; padding: 5px 12px; background: #fff; border-color: var(--moa-gold); {{ $selectedPatient && $selectedPatient->id == $qp->patient_id ? 'font-weight: bold; border-color: var(--moa-bronze); color: var(--moa-bronze);' : '' }}">
            <span class="ticket-badge" style="font-size: 11px; padding: 2px 5px; margin-right: 4px;">{{ $qp->ticket_number }}</span>
            {{ $qp->patient_name }}
        </a>
        @endforeach
    </div>
</div>
@endif

<form method="POST" action="{{ route('admin.examinations.store') }}" data-admin-form>
    @csrf

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 24px;">
        <!-- Left Column: Patient Selection & Clinical Intake -->
        <section class="admin-panel" style="margin-top: 0;">
            <div class="admin-section-head">
                <div>
                    <h2>Patient identification & intake</h2>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 14px;">
                <label style="display: flex; flex-direction: column; gap: 5px; font-weight: 600; font-size: 13px;">
                     <span class="required">Select Patient</span>
                    <select name="patient_id" required style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 14px;" onchange="window.location.href='{{ route('admin.clinical') }}?patient_id=' + this.value">
                        <option value="">-- Choose Patient --</option>
                        @foreach($allPatients as $pat)
                            <option value="{{ $pat->id }}" @selected($selectedPatient && $selectedPatient->id == $pat->id)>
                                {{ $pat->name }} ({{ $pat->email }} · {{ $pat->phone ?: 'No phone' }})
                            </option>
                        @endforeach
                    </select>
                </label>

                @if($selectedAppointment)
                    <input type="hidden" name="appointment_id" value="{{ $selectedAppointment->id }}">
                    <div style="background: #F8F5F0; border: 1px solid #E5DFD7; border-radius: 8px; padding: 12px 14px; font-size: 13px;">
                        <strong>Linked Appointment:</strong> {{ $selectedAppointment->service }} with {{ $selectedAppointment->specialist ?: 'Optometry Team' }}
                        <br><small style="color: var(--moa-slate);">Scheduled: {{ $selectedAppointment->starts_at->format('M d, Y · g:i A') }}</small>
                        @if($selectedAppointment->digitalIntake)
                            <div style="margin-top: 8px; padding-top: 8px; border-top: 1px dashed #DDD;">
                                <strong>Pre-Visit Intake:</strong>
                                <br><small>Symptoms: {{ $selectedAppointment->digitalIntake->symptoms ?: 'None recorded' }}</small>
                                <br><small>Medical History: {{ $selectedAppointment->digitalIntake->medical_history ?: 'None' }}</small>
                                @if($selectedAppointment->digitalIntake->hmo_provider_name)
                                    <br><small>HMO: <strong>{{ $selectedAppointment->digitalIntake->hmo_provider_name }}</strong> (Card: {{ $selectedAppointment->digitalIntake->hmo_member_id }})</small>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                <label style="display: flex; flex-direction: column; gap: 5px; font-weight: 600; font-size: 13px;">
                    Chief Complaint / History of Present Illness
                    <textarea name="chief_complaint" rows="3" placeholder="e.g. Progressive blurriness at near distance since 6 months; asthenopia during computer usage." style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px; font-family: inherit;"></textarea>
                </label>

                <!-- Visual Acuity -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 6px;">
                    <label style="display: flex; flex-direction: column; gap: 5px; font-weight: 600; font-size: 12px;">
                        Visual Acuity OD (Right Eye)
                        <input type="text" name="visual_acuity_od" placeholder="e.g. 20/40" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
                    </label>
                    <label style="display: flex; flex-direction: column; gap: 5px; font-weight: 600; font-size: 12px;">
                        Visual Acuity OS (Left Eye)
                        <input type="text" name="visual_acuity_os" placeholder="e.g. 20/30" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
                    </label>
                </div>

                <!-- Tonometry & Ocular Health -->
                <div style="background: #FAFAF8; border: 1px solid #EBE4DC; border-radius: 8px; padding: 14px; margin-top: 6px;">
                    <div style="font-weight: 700; font-size: 12px; color: var(--moa-bronze); margin-bottom: 8px;">TONOMETRY (INTRAOCULAR PRESSURE)</div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <label style="font-size: 12px; display: flex; flex-direction: column; gap: 4px;">
                            IOP OD (mmHg)
                            <input type="number" step="0.5" name="iop_od" placeholder="e.g. 15.0" style="padding: 6px 8px; border: 1px solid #D5CBC0; border-radius: 4px;">
                        </label>
                        <label style="font-size: 12px; display: flex; flex-direction: column; gap: 4px;">
                            IOP OS (mmHg)
                            <input type="number" step="0.5" name="iop_os" placeholder="e.g. 15.5" style="padding: 6px 8px; border: 1px solid #D5CBC0; border-radius: 4px;">
                        </label>
                    </div>
                </div>

                <!-- Biomicroscopy / Slit Lamp & Fundus Notes -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 12px;">
                        Anterior Segment / Slit Lamp
                        <textarea name="slit_lamp_notes" rows="2" placeholder="Cornea clear, anterior chamber deep & quiet, lens clear OU" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 12px; font-family: inherit;"></textarea>
                    </label>
                    <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 12px;">
                        Fundus / Posterior Segment
                        <textarea name="fundus_notes" rows="2" placeholder="Optic disc pink, distinct margins, C/D 0.3 OU, macula intact" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 12px; font-family: inherit;"></textarea>
                    </label>
                </div>
            </div>
        </section>

        <!-- Right Column: Full Binocular Refraction & Prescription Signing -->
        <section class="admin-panel" style="margin-top: 0;">
            <div class="admin-section-head">
                <div>
                    <h2>Optical refraction grid</h2>
                </div>
            </div>

            <table class="ehr-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Eye</th>
                        <th>Sphere (SPH)</th>
                        <th>Cylinder (CYL)</th>
                        <th>Axis (0-180°)</th>
                        <th>Add (ADD)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong style="color: var(--moa-bronze);">OD (Right)</strong></td>
                        <td><input type="number" step="0.25" name="sph_od" placeholder="-1.50"></td>
                        <td><input type="number" step="0.25" name="cyl_od" placeholder="-0.75"></td>
                        <td><input type="number" name="axis_od" min="0" max="180" placeholder="90"></td>
                        <td><input type="number" step="0.25" name="add_od" placeholder="+1.25"></td>
                    </tr>
                    <tr>
                        <td><strong style="color: var(--moa-bronze);">OS (Left)</strong></td>
                        <td><input type="number" step="0.25" name="sph_os" placeholder="-1.75"></td>
                        <td><input type="number" step="0.25" name="cyl_os" placeholder="-0.50"></td>
                        <td><input type="number" name="axis_os" min="0" max="180" placeholder="85"></td>
                        <td><input type="number" step="0.25" name="add_os" placeholder="+1.25"></td>
                    </tr>
                </tbody>
            </table>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 14px;">
                <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 12px;">
                    Pupillary Distance (PD mm)
                    <input type="number" step="0.5" name="pd" placeholder="e.g. 63.5" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
                </label>
                <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 12px;">
                    Rx Prescription Modality
                    <select name="rx_type" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
                        <option value="spectacle">Spectacle / Eyeglass Lenses</option>
                        <option value="contact_lens">Contact Lens Fitting</option>
                    </select>
                </label>
            </div>

            <!-- Diagnosis & Plan -->
            <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 16px;">
                <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 12px;">
                    Clinical Assessment & Diagnosis
                    <input type="text" name="diagnosis" placeholder="e.g. Compound Myopic Astigmatism OU, Presbyopia" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
                </label>
                <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 12px;">
                    Clinical Management & Dispensing Instructions
                    <textarea name="management_plan" rows="2" placeholder="Prescribe progressive lenses with anti-reflective coating. Annual recall recommended in 12 months." style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 12px; font-family: inherit;"></textarea>
                </label>
            </div>

            <!-- Digital Signature Seal -->
            <div class="digital-stamp">
                <div class="stamp-seal"><i class="bx bx-check-shield"></i></div>
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #164e63; font-size: 13px;">Digital Clinical Signature & Tamper-Evident Seal</div>
                    <div style="font-size: 12px; color: #555; line-height: 1.4;">
                        Signed by <strong>{{ auth()->user()->name }}</strong> (Optometrist/Doctor).
                        Submitting this examination generates an immutable SHA-256 cryptographic verification token recorded in the clinic audit log.
                    </div>
                </div>
            </div>

            <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid #EBE4DC; display: flex; justify-content: space-between; align-items: center;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="issue_prescription" value="1" checked style="width: 18px; height: 18px;">
                    Sign & issue active prescription to patient vault
                </label>
                <button class="button button-teal button-primary" type="submit" data-admin-submit style="padding: 10px 22px;">
                    Sign & Record Examination <i class="bx bx-check"></i>
                </button>
            </div>
        </section>
    </div>
</form>

<!-- Recent Examinations Log -->
<section class="admin-panel" style="margin-top: 24px;">
    <div class="admin-section-head">
        <div>
            <h2>Recent clinical examinations</h2>
            <p>Archived examination records and digitally signed refractive data.</p>
        </div>
    </div>

    <div class="admin-record-list">
        @forelse($recentExaminations as $exam)
        <div class="admin-record" style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <strong style="font-size: 15px;">{{ $exam->patient->name }}</strong>
                    <span class="staff-role-badge">Exam #{{ $exam->id }}</span>
                    <span style="font-size: 12px; color: var(--moa-slate);">
                        by Dr. {{ $exam->optometrist->name }} on {{ $exam->created_at->format('M d, Y · g:i A') }}
                    </span>
                </div>
                <p style="margin: 6px 0 4px; font-size: 13px;">
                    <strong>OD:</strong> SPH {{ $exam->sph_od ?? '—' }} · CYL {{ $exam->cyl_od ?? '—' }} · AXIS {{ $exam->axis_od ?? '—' }} | 
                    <strong>OS:</strong> SPH {{ $exam->sph_os ?? '—' }} · CYL {{ $exam->cyl_os ?? '—' }} · AXIS {{ $exam->axis_os ?? '—' }} | 
                    <strong>PD:</strong> {{ $exam->pd ?? '—' }} mm | 
                    <strong>IOP:</strong> {{ $exam->iop_od ?? '—' }}/{{ $exam->iop_os ?? '—' }} mmHg
                </p>
                @if($exam->diagnosis)
                    <div style="font-size: 12px; color: #555;"><em>Diagnosis: {{ $exam->diagnosis }}</em></div>
                @endif
                @if($exam->digital_signature_token)
                    <small style="color: var(--moa-slate); font-family: monospace; font-size: 11px;">
                        Token: {{ substr($exam->digital_signature_token, 0, 24) }}...
                    </small>
                @endif
            </div>
            <div style="text-align: right;">
                <span class="record-status" style="background: rgba(100, 188, 180, 0.15); color: #164e63;">
                    <i class="bx bx-check-shield"></i> Signed
                </span>
            </div>
        </div>
        @empty
        <div class="admin-empty">No clinical examinations recorded yet.</div>
        @endforelse
    </div>
    {{ $recentExaminations->links() }}
</section>
@endsection

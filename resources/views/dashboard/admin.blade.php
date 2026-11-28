@extends('admin.layout')

@section('title', 'Executive Clinic Dashboard')

@section('content')
<div class="admin-page-head" id="overview">
    <div>
        <h1>Clinic <em>operations & analytics.</em></h1>
        <div>Real-time clinical throughput, scheduling, patient EHR, and inventory governance.</div>
    </div>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <form method="POST" action="{{ route('admin.recalls.trigger') }}" style="margin: 0;">
            @csrf
            <button class="button button-quiet" type="submit" title="Scan patient database for annual exams and expiring contact lens prescriptions">
                <i class="bx bx-bolt-circle"></i> Run Recall Scan
            </button>
        </form>
        <a href="{{ route('admin.queue') }}" class="button" style="background: var(--moa-bronze); color: #fff;">
            Front-Desk Queue
        </a>
    </div>
</div>

@if(session('success'))
    <p class="portal-feedback" role="status" style="border-left: 4px solid var(--moa-teal); background: rgba(100, 188, 180, 0.12); color: #164e63;">
        <i class="bx bx-check-circle"></i> {{ session('success') }}
    </p>
@endif

@if($errors->any())
    <div class="admin-validation" role="alert">
        <strong>Check the highlighted request:</strong>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<!-- 5 Executive KPI Widgets -->
<div class="kpi-grid-5">
    <!-- 1. Daily Appointments -->
    <div class="kpi-card-custom kpi-appointments">
        <div class="kpi-header">
            <span>Upcoming Appointments</span>
            <span style="color: var(--moa-teal); font-size: 16px;"><i class="bx bx-calendar"></i></span>
        </div>
        <div class="kpi-value">{{ $kpis['today_appointments'] }}</div>
        <div class="kpi-sub">
            <strong>{{ $kpis['today_completed'] }}</strong> completed today · {{ $kpis['active_queue_count'] }} in clinic queue
        </div>
    </div>

    <div class="kpi-card-custom kpi-lab">
        <div class="kpi-header">
            <span>Lens Status</span>
            <span style="color: var(--moa-gold); font-size: 16px;"><i class="bx bx-cog"></i></span>
        </div>
        <div class="kpi-value">{{ $kpis['pending_lab_orders'] }}</div>
        <div class="kpi-sub">
            Active in surfacing, edging, tinting or quality check
        </div>
    </div>

    <!-- 2. No-Show & Cancellation Rate --> 
    

    <!-- 5. Low-Stock Inventory Alerts -->
    <div class="kpi-card-custom kpi-inventory">
        <div class="kpi-header">
            <span>Dispensing</span>
            <span style="color: #E0564C; font-size: 16px;"><i class="bx bx-error-alt"></i></span>
        </div>
        <div class="kpi-value" style="color: {{ $kpis['low_stock_count'] > 0 ? '#E0564C' : 'inherit' }};">
            {{ $kpis['low_stock_count'] }}
        </div>
        <div class="kpi-sub">
            Frames at or below reorder threshold
        </div>
    </div>

    <div class="kpi-card-custom kpi-noshow">
        <div class="kpi-header">
            <span>Cancelled Appointments</span>
            <span style="color: #cf1d11; font-size: 16px;"><i class="bx bx-x-circle"></i></span>
        </div>
        <div class="kpi-value">{{ $kpis['no_show_rate'] }}%</div>
        <div class="kpi-sub">
            Calculated across {{ $kpis['total_past'] }} recorded past visits
        </div>
    </div>
</div>

<!-- Low Stock Inventory Alert Panel (if items low) -->
@if($lowStockFrames->isNotEmpty())
<section class="admin-panel" id="inventory" style="border-left: 4px solid #E0564C;">
    <div class="admin-section-head">
        <div>
            <h2>Low Stock Reorder Alerts</h2>
            <p>Frames that have breached minimum safe stock thresholds and require supplier procurement.</p>
        </div>
    </div>
    <div class="admin-record-list">
        @foreach($lowStockFrames as $frame)
        <div class="admin-record" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong>{{ $frame->brand }} — {{ $frame->model_name }}</strong>
                <small>SKU: {{ $frame->sku }} · {{ ucfirst($frame->material) }} · {{ ucfirst($frame->shape) }} · Supplier: {{ $frame->supplier_name ?: 'Internal Stock' }}</small>
            </div>
            <div style="text-align: right;">
                <span class="record-status" style="background: rgba(224, 86, 76, 0.12); color: #C53030; font-weight: 700;">
                    {{ $frame->stock_quantity }} left (Min: {{ $frame->reorder_threshold }})
                </span>
                <div><small>Retail: ₱{{ number_format($frame->retail_price, 2) }}</small></div>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif

<!-- Doctor Availability -->
<section class="admin-panel" id="availability">
    <div class="admin-section-head">
        <div>
            <h2>Doctor availability</h2>
            <p>Publish weekday booking blocks for patient self-service appointments.</p>
        </div>
        <a href="{{ route('admin.scheduling') }}" class="button button-quiet">Manage Doctor Shifts & Leaves <i class="bx bx-right-arrow-alt"></i></a>
    </div>
    <form method="POST" action="{{ route('admin.availability.store') }}" class="admin-availability-form" data-admin-form>
        @csrf
        <label>Starts<input type="datetime-local" name="starts_at" required></label>
        <label>Ends<input type="datetime-local" name="ends_at" required></label>
        <button class="button button-teal button-primary" type="submit" data-admin-submit>Add availability</button>
    </form>
    <div class="admin-record-list">
        @forelse($availability as $slot)
        <div class="admin-record">
            <div class="admin-record-date">
                <strong>{{ $slot->starts_at->format('d') }}</strong>
                <span>{{ $slot->starts_at->format('M') }}</span>
            </div>
            <div>
                <strong>{{ $slot->starts_at->format('l, g:i A') }}</strong>
                <small>Until {{ $slot->ends_at->format('g:i A') }}</small>
            </div>
            <span class="record-status">Available</span>
        </div>
        @empty
        <div class="admin-empty">No future availability published.</div>
        @endforelse
    </div>
</section>

<!-- Appointments Stream -->
<section class="admin-panel" id="appointments">
    <div class="admin-section-head">
        <div>
            <h2>Care Queue & Appointments</h2>
            <p>Review booking requests, assign exam rooms, and update appointment states.</p>
        </div>
        <a href="{{ route('admin.queue') }}" class="button button-quiet">Live Reception Desk <i class="bx bx-right-arrow-alt"></i></a>
    </div>
    <div class="admin-record-list">
        @forelse($appointments as $appointment)
        <article class="admin-appointment">
            <div class="admin-record-date">
                <strong>{{ $appointment->starts_at->format('d') }}</strong>
                <span>{{ $appointment->starts_at->format('M') }}</span>
            </div>
            <div class="admin-appointment-main">
                <div class="eyebrow">{{ $appointment->starts_at->format('l · g:i A') }} ({{ $appointment->duration_minutes }} min)</div>
                <h3>{{ $appointment->user->name }}</h3>
                <p>
                    {{ $appointment->service }}
                    {{ $appointment->doctor ? ' · '.$appointment->doctor->name : '' }}
                    @if($appointment->exam_room)
                        · <span style="color: var(--moa-bronze); font-weight: 600;">{{ $appointment->exam_room }}</span>
                    @endif
                </p>
            </div>
            <span class="record-status status-{{ $appointment->status }}">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span>
            <form class="admin-update-form" method="POST" action="{{ route('admin.appointments.update', $appointment) }}" data-admin-form>
                @csrf
                @method('PATCH')
                <select name="status" aria-label="Appointment status">
                    <option value="requested" @selected($appointment->status === 'requested')>Requested</option>
                    <option value="confirmed" @selected($appointment->status === 'confirmed')>Confirmed</option>
                    <option value="in_progress" @selected($appointment->status === 'in_progress')>In progress</option>
                    <option value="completed" @selected($appointment->status === 'completed')>Completed</option>
                    <option value="rescheduled" @selected($appointment->status === 'rescheduled')>Rescheduled</option>
                    <option value="rejected" @selected($appointment->status === 'rejected')>Rejected</option>
                    <option value="cancelled" @selected($appointment->status === 'cancelled')>Cancelled</option>
                    <option value="no_show" @selected($appointment->status === 'no_show')>No-Show</option>
                </select>
                <input type="datetime-local" name="starts_at" value="{{ $appointment->starts_at->format('Y-m-d\TH:i') }}" aria-label="Appointment time">
                <input name="exam_room" value="{{ $appointment->exam_room }}" placeholder="Room" style="max-width: 110px;" aria-label="Room">
                <input name="reason" placeholder="Reason if status change" aria-label="Reason">
                <button class="button button-quiet" type="submit" data-admin-submit>Update</button>
            </form>
        </article>
        @empty
        <div class="admin-empty">No appointments found.</div>
        @endforelse
    </div>
    {{ $appointments->links() }}
</section>

<!-- Patients Section -->
<section class="admin-panel" id="patients">
    <div class="admin-section-head">
        <div>
            <h2>Patient directory</h2>
            <p>Direct access to patient medical summaries and quick prescription entries.</p>
        </div>
        <a href="{{ route('admin.clinical') }}" class="button button-quiet">Launch EHR Refraction Suite <i class="bx bx-right-arrow-alt"></i></a>
    </div>
    <div class="admin-record-list">
        @forelse($patients as $patient)
        <article class="admin-patient">
            <div class="patient-avatar">{{ strtoupper(substr($patient->name, 0, 1)) }}</div>
            <div>
                <h3>{{ $patient->name }}</h3>
                <p>{{ $patient->email }} · {{ $patient->phone ?: 'No phone on file' }}</p>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <a href="{{ route('admin.clinical', ['patient_id' => $patient->id]) }}" class="button button-quiet" style="font-size: 12px; padding: 6px 12px;">
                    EHR Exam
                </a>
                <details>
                    <summary>Quick Rx</summary>
                    <form method="POST" action="{{ route('admin.prescriptions.store', $patient) }}" class="prescription-form" data-admin-form>
                        @csrf
                        <div class="prescription-form-grid">
                            <label>Eye<select name="eye" required><option value="OD">OD · Right</option><option value="OS">OS · Left</option></select></label>
                            <label>Sphere<input type="number" step="0.25" name="sphere"></label>
                            <label>Cylinder<input type="number" step="0.25" name="cylinder"></label>
                            <label>Axis<input type="number" name="axis" min="0" max="180"></label>
                            <label>PD<input type="number" step="0.1" name="pd"></label>
                            <label>Issued<input type="date" name="issued_at" value="{{ now()->toDateString() }}" required></label>
                            <label>Expires<input type="date" name="expires_at" value="{{ now()->addYears(2)->toDateString() }}"></label>
                        </div>
                        <button class="button button-teal" type="submit" data-admin-submit>Save & Sign</button>
                    </form>
                </details>
            </div>
        </article>
        @empty
        <div class="admin-empty">No patients registered.</div>
        @endforelse
    </div>
    {{ $patients->links() }}
</section>

<!-- Prescriptions History -->
<section class="admin-panel" id="prescriptions">
    <div class="admin-section-head">
        <div>
            <h2>Prescription vault</h2>
            <p>Signed spectacle and contact lens prescriptions issued by clinic optometrists.</p>
        </div>
    </div>
    <div class="admin-record-list">
        @forelse($prescriptions as $prescription)
        <div class="admin-prescription">
            <span class="prescription-eye">{{ $prescription->eye }}</span>
            <div style="flex: 1;">
                <h3>{{ $prescription->user->name }} ({{ ucfirst($prescription->rx_type) }})</h3>
                <p>
                    Issued {{ $prescription->issued_at->format('M j, Y') }} · 
                    SPH {{ $prescription->sphere ?? '—' }} · 
                    CYL {{ $prescription->cylinder ?? '—' }} · 
                    AXIS {{ $prescription->axis ?? '—' }} · 
                    PD {{ $prescription->pd ?? '—' }} · 
                    ADD {{ $prescription->add ?? '—' }}
                </p>
                @if($prescription->signature_token)
                <small style="color: var(--moa-slate); font-family: monospace; font-size: 11px;">
                    Verified SHA-256 Token: {{ substr($prescription->signature_token, 0, 16) }}...
                </small>
                @endif
            </div>
        </div>
        @empty
        <div class="admin-empty">No prescriptions on file.</div>
        @endforelse
    </div>
    {{ $prescriptions->links() }}
</section>

<!-- Audit Log Stream -->
<section class="admin-panel" id="audit">
    <div class="admin-section-head">
        <div>
            <h2>Live Audit Trail</h2>
            <p>Immutable log of clinical modifications, staff actions, and sensitive record views.</p>
        </div>
    </div>
    <div class="audit-stream">
        @forelse($recentAuditLogs as $log)
        <div class="audit-row">
            <div>
                <span class="audit-action-tag">{{ $log->action }}</span>
                <span style="margin-left: 8px;">
                    by <strong>{{ $log->actor ? $log->actor->name : 'System / Automated Job' }}</strong>
                </span>
            </div>
            <div style="color: var(--moa-slate); font-size: 11px;">
                {{ $log->created_at->format('M d, Y · g:i A') }} ({{ $log->created_at->diffForHumans() }})
            </div>
        </div>
        @empty
        <div class="admin-empty">No audit events recorded yet.</div>
        @endforelse
    </div>
</section>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-admin-form]').forEach((form) => {
    form.addEventListener('submit', () => {
        const submit = form.querySelector('[data-admin-submit]');
        if (!submit) return;
        submit.disabled = true;
        submit.setAttribute('aria-busy', 'true');
        submit.firstChild.textContent = 'Saving…';
    });
});
</script>
@endpush

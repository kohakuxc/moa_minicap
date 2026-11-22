@extends('admin.layout')

@section('title', 'Master Scheduling & Clinic Configuration')

@section('content')
<div class="admin-page-head">
    <div>
        <h1>Clinic <em>schedules & services.</em></h1>
        <p>Configure doctor shift availability, manage leave blocks, set service slot durations, and assign exam suites.</p>
    </div>
</div>

@if(session('success'))
    <p class="portal-feedback" role="status" style="border-left: 4px solid var(--moa-teal); background: rgba(100, 188, 180, 0.12); color: #164e63;">
        <i class="bx bx-check-circle"></i> {{ session('success') }}
    </p>
@endif

@if($errors->any())
    <div class="admin-validation" role="alert">
        <strong>Check your inputs:</strong>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 24px;">
    <!-- 1. Doctor Shift Availability -->
    <section class="admin-panel" style="margin-top: 0;">
        <div class="admin-section-head">
            <div>
                <h2>Doctor availability slots</h2>
                <p>Weekday timeslots open for patient booking.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.availability.store') }}" class="admin-availability-form" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
            @csrf
            <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                Shift Starts
                <input type="datetime-local" name="starts_at" required style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
            </label>
            <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                Shift Ends
                <input type="datetime-local" name="ends_at" required style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
            </label>
            <button class="button button-teal" type="submit" style="grid-column: 1 / -1; justify-self: start;">
                Publish Shift
            </button>
        </form>

        <div class="admin-record-list">
            @forelse($availabilities as $slot)
            <div class="admin-record">
                <div class="admin-record-date">
                    <strong>{{ $slot->starts_at->format('d') }}</strong>
                    <span>{{ $slot->starts_at->format('M') }}</span>
                </div>
                <div>
                    <strong>{{ $slot->starts_at->format('l, g:i A') }} – {{ $slot->ends_at->format('g:i A') }}</strong>
                    <small>Doctor: {{ $slot->doctor->name }}</small>
                </div>
                <span class="record-status" style="background: rgba(100, 188, 180, 0.15); color: #164e63;">Active</span>
            </div>
            @empty
            <div class="admin-empty">No active shifts scheduled.</div>
            @endforelse
        </div>
    </section>

    <!-- 2. Doctor Leave Blocks -->
    <section class="admin-panel" style="margin-top: 0;">
        <div class="admin-section-head">
            <div>
                <h2>Leave & blackout blocks</h2>
                <p>Block patient bookings during leave, conferences, or off-duty periods.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.leave.store') }}" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
            @csrf
            <label style="grid-column: 1 / -1; display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                Doctor
                <select name="doctor_id" required style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
                    @foreach($doctors as $doc)
                        <option value="{{ $doc->id }}">{{ $doc->name }} ({{ ucfirst($doc->role) }})</option>
                    @endforeach
                </select>
            </label>
            <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                Leave Starts
                <input type="datetime-local" name="starts_at" required style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
            </label>
            <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                Leave Ends
                <input type="datetime-local" name="ends_at" required style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
            </label>
            <label style="grid-column: 1 / -1; display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                Reason
                <input type="text" name="reason" placeholder="e.g. Annual Medical Leave, Ophthalmology Conference" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
            </label>
            <button class="button button-quiet" type="submit" style="grid-column: 1 / -1; justify-self: start;">
                Register Leave Block
            </button>
        </form>

        <div class="admin-record-list">
            @forelse($leaveBlocks as $block)
            <div class="admin-record" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong>{{ $block->doctor->name }}</strong>
                    <p style="margin: 2px 0 0; font-size: 12px; color: var(--moa-slate);">
                        {{ $block->starts_at->format('M d, Y g:i A') }} – {{ $block->ends_at->format('M d, Y g:i A') }}
                        @if($block->reason) · <em>"{{ $block->reason }}"</em> @endif
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.leave.destroy', $block) }}" onsubmit="return confirm('Remove this leave block?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" style="color: #c53030; background: none; border: none; font-size: 12px; cursor: pointer;">
                        Remove <i class="bx bx-x"></i>
                    </button>
                </form>
            </div>
            @empty
            <div class="admin-empty">No active doctor leave blocks on file.</div>
            @endforelse
        </div>
    </section>
</div>

<!-- 3. Clinic Services & Slot Durations -->
<section class="admin-panel" style="margin-top: 24px;">
    <div class="admin-section-head">
        <div>
            <h2>Optical services & slot durations</h2>
            <p>Define consultation durations, exam room needs, and standard pricing.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.services.store') }}" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 12px; align-items: end; background: #FAF7F2; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
        @csrf
        <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
            Service Name
            <input type="text" name="name" placeholder="e.g. Scleral Lens Assessment" required style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
        </label>
        <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
            Category
            <select name="category" required style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
                <option value="clinical">Clinical Exam</option>
                <option value="optical">Optical / Dispensing</option>
                <option value="consultation">Consultation</option>
            </select>
        </label>
        <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
            Duration (min)
            <input type="number" name="duration_minutes" value="30" min="10" max="180" required style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
        </label>
        <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
            Fee (PHP)
            <input type="number" step="0.01" name="fee" value="850.00" required style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
        </label>
        <button class="button button-dark" type="submit" style="padding: 9px 18px;">
            Add Service <i class="bx bx-plus"></i>
        </button>
    </form>

    <div style="overflow-x: auto;">
        <table class="ehr-table">
            <thead>
                <tr>
                    <th>Service Name</th>
                    <th>Category</th>
                    <th>Slot Duration</th>
                    <th>Consultation Fee</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($services as $svc)
                <tr>
                    <td>
                        <strong>{{ $svc->name }}</strong>
                        @if($svc->description)<br><small style="color: var(--moa-slate);">{{ Str::limit($svc->description, 80) }}</small>@endif
                    </td>
                    <td><span class="staff-role-badge">{{ ucfirst($svc->category) }}</span></td>
                    <td><strong>{{ $svc->duration_minutes }} minutes</strong></td>
                    <td>₱{{ number_format($svc->fee, 2) }}</td>
                    <td>
                        <span class="record-status" style="background: {{ $svc->is_active ? 'rgba(100, 188, 180, 0.18)' : '#eee' }}; color: {{ $svc->is_active ? '#164e63' : '#777' }};">
                            {{ $svc->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.services.toggle', $svc) }}" style="margin: 0;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="button button-quiet" style="font-size: 11px; padding: 4px 10px;">
                                {{ $svc->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<!-- 4. Exam Room Suite Overview -->
<section class="admin-panel" style="margin-top: 24px;">
    <div class="admin-section-head">
        <div>
            <h2>Exam suites & diagnostic stations</h2>
            <p>Physical room allocation for appointments and patient queue distribution.</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 14px;">
        @foreach($examRooms as $index => $room)
        <div style="background: #fff; border: 1px solid #E5DFD7; border-radius: 8px; padding: 16px; border-left: 4px solid var(--moa-bronze);">
            <div style="font-size: 11px; text-transform: uppercase; color: var(--moa-slate); font-weight: 700;">Suite #{{ $index + 1 }}</div>
            <h3 style="margin: 6px 0 4px; font-size: 14px;">{{ $room }}</h3>
            <small style="color: var(--moa-teal); font-weight: 600;">● Available for scheduled care</small>
        </div>
        @endforeach
    </div>
</section>
@endsection

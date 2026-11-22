@extends('admin.layout')

@section('title', 'Front-Desk & Queue Management')

@section('content')
<div class="admin-page-head">
    <div>
        <h1>Waiting room & <em>queue management.</em></h1>
        <p>Walk-in token generation, appointment check-ins, room routing, and live patient status tracking.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <a href="{{ route('admin.live-queue') }}" target="_blank" class="button button-quiet" title="Open large waiting room display screen in new tab">
            <i class="bx bx-tv"></i> Launch TV Wallboard <i class="bx bx-export"></i>
        </a>
    </div>
</div>

@if(session('success'))
    <p class="portal-feedback" role="status" style="border-left: 4px solid var(--moa-teal); background: rgba(100, 188, 180, 0.12); color: #164e63;">
        <i class="bx bx-check-circle"></i> {{ session('success') }}
    </p>
@endif

@if(session('info'))
    <p class="portal-feedback" role="status" style="border-left: 4px solid var(--moa-gold); background: rgba(240, 186, 127, 0.15); color: #7A5530;">
        <i class="bx bx-info-circle"></i> {{ session('info') }}
    </p>
@endif

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px; margin-bottom: 24px;">
    <!-- 1. Walk-In Token Generator -->
    <section class="admin-panel" style="margin-top: 0;">
        <div class="admin-section-head">
            <div>
                <h2>Generate walk-in ticket</h2>
                <p>Issue an instant token for unscheduled arrivals.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.queue.walk-in') }}" style="display: flex; flex-direction: column; gap: 12px;" data-admin-form>
            @csrf
            <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                 <span class="required">Patient Name</span>
                <input type="text" name="patient_name" placeholder="Full name of walk-in patient" required style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
            </label>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                    Existing Account (Optional)
                    <select name="patient_id" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
                        <option value="">-- Guest Walk-in --</option>
                        @foreach($patients as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                    Requested Service
                    <select name="service_id" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
                        <option value="">-- General Consultation --</option>
                        @foreach($services as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->duration_minutes }}m)</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                Assigned Exam Room / Station
                <input type="text" name="assigned_room" value="Room 1 - Comprehensive Refraction Suite" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
            </label>

            <button class="button button-teal" type="submit" data-admin-submit style="margin-top: 4px; justify-self: start;">
                Issue Walk-in Ticket (W-xxx) <i class="bx bx-plus"></i>
            </button>
        </form>
    </section>

    <!-- 2. Today's Appointment Check-Ins -->
    <section class="admin-panel" style="margin-top: 0;">
        <div class="admin-section-head">
            <div>
                <h2>Check-in today's appointments</h2>
                <p>Welcome scheduled patients and issue priority tokens (A-xxx).</p>
            </div>
        </div>

        <div class="admin-record-list" style="max-height: 280px; overflow-y: auto;">
            @forelse($todayAppointments as $apt)
            <div class="admin-record" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px;">
                <div>
                    <div style="font-weight: 700; font-size: 14px;">{{ $apt->user->name }}</div>
                    <small style="color: var(--moa-slate);">
                        {{ $apt->starts_at->format('g:i A') }} · {{ $apt->service }}
                        @if($apt->doctor) · with {{ $apt->doctor->name }} @endif
                    </small>
                </div>
                <div>
                    @if($apt->checked_in_at)
                        <span class="record-status" style="background: rgba(100, 188, 180, 0.15); color: #164e63;">
                            Checked In ({{ $apt->check_in_token }})
                        </span>
                    @else
                        <form method="POST" action="{{ route('admin.queue.check-in', $apt) }}" style="margin: 0;">
                            @csrf
                            <button type="submit" class="button button-quiet" style="font-size: 12px; padding: 6px 12px;">
                                Check In
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            @empty
            <div class="admin-empty">No remaining appointments scheduled for today.</div>
            @endforelse
        </div>
    </section>
</div>

<!-- 3. Live 4-Column Waiting Room Board -->
<section class="admin-panel" style="margin-top: 0;">
    <div class="admin-section-head">
        <div>
            <h2>Clinic floor queue board</h2>
            <p>Drag or advance patients through the triage, exam, dispensing, and checkout pipeline.</p>
        </div>
    </div>

    <div class="queue-board-grid">
        <!-- Column 1: Waiting Room -->
        <div class="queue-col">
            <div class="queue-col-header">
                <span style="color: var(--moa-slate);">1. WAITING ROOM</span>
                <span class="queue-badge-count">{{ $waitingTickets->count() }}</span>
            </div>
            @forelse($waitingTickets as $t)
            <div class="queue-card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                    <span class="ticket-badge">{{ $t->ticket_number }}</span>
                    <small style="color: var(--moa-slate);">{{ $t->check_in_time->diffForHumans() }}</small>
                </div>
                <div style="font-weight: 700; font-size: 14px;">{{ $t->patient_name }}</div>
                <div style="font-size: 12px; color: #666; margin: 4px 0 10px;">
                    {{ $t->clinicService ? $t->clinicService->name : 'General Care' }}
                    @if($t->assigned_room)<br><small style="color: var(--moa-bronze);">{{ $t->assigned_room }}</small>@endif
                </div>
                <form method="POST" action="{{ route('admin.queue.ticket.update', $t) }}" style="margin: 0; display: flex; gap: 6px;">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="in_consultation">
                    <button type="submit" class="button button-teal" style="font-size: 11px; padding: 5px 10px; width: 100%;">
                        Call Next
                    </button>
                </form>
            </div>
            @empty
            <div style="text-align: center; color: var(--moa-slate); font-size: 13px; padding: 20px;">No patients waiting.</div>
            @endforelse
        </div>

        <!-- Column 2: In Consultation -->
        <div class="queue-col" style="background: rgba(100, 188, 180, 0.05); border-color: rgba(100, 188, 180, 0.4);">
            <div class="queue-col-header" style="color: #164e63;">
                <span>2. IN CONSULTATION</span>
                <span class="queue-badge-count" style="background: var(--moa-teal); color: #fff;">{{ $inConsultationTickets->count() }}</span>
            </div>
            @forelse($inConsultationTickets as $t)
            <div class="queue-card" style="border-left: 3px solid var(--moa-teal);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                    <span class="ticket-badge" style="background: rgba(100, 188, 180, 0.2); color: #164e63;">{{ $t->ticket_number }}</span>
                    <small style="color: var(--moa-teal); font-weight: 600;">Called {{ $t->called_at ? $t->called_at->format('g:i A') : 'now' }}</small>
                </div>
                <div style="font-weight: 700; font-size: 14px;">{{ $t->patient_name }}</div>
                <div style="font-size: 12px; color: var(--moa-bronze); margin: 4px 0 10px; font-weight: 600;">
                    <i class="bx bx-map-pin"></i> {{ $t->assigned_room ?: 'Exam Room 1' }}
                </div>
                <div style="display: flex; gap: 6px;">
                    <a href="{{ route('admin.clinical', ['patient_id' => $t->patient_id, 'appointment_id' => $t->appointment_id]) }}" class="button button-quiet" style="font-size: 11px; padding: 5px 8px; flex: 1; text-align: center;">
                        EHR Exam <i class="bx bx-right-arrow-alt"></i>
                    </a>
                    <form method="POST" action="{{ route('admin.queue.ticket.update', $t) }}" style="margin: 0; flex: 1;">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="dispensing">
                        <button type="submit" class="button" style="font-size: 11px; padding: 5px 8px; width: 100%; background: var(--moa-bronze); color: #fff;">
                            To Dispensing <i class="bx bx-right-arrow-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div style="text-align: center; color: var(--moa-slate); font-size: 13px; padding: 20px;">No ongoing exams.</div>
            @endforelse
        </div>

        <!-- Column 3: Optical Dispensing -->
        <div class="queue-col" style="background: rgba(240, 186, 127, 0.08); border-color: rgba(240, 186, 127, 0.5);">
            <div class="queue-col-header" style="color: var(--moa-bronze);">
                <span>3. OPTICAL DISPENSING</span>
                <span class="queue-badge-count" style="background: var(--moa-gold); color: #553C00;">{{ $dispensingTickets->count() }}</span>
            </div>
            @forelse($dispensingTickets as $t)
            <div class="queue-card" style="border-left: 3px solid var(--moa-gold);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                    <span class="ticket-badge">{{ $t->ticket_number }}</span>
                    <small style="color: var(--moa-bronze); font-weight: 600;">At Frame Bar</small>
                </div>
                <div style="font-weight: 700; font-size: 14px;">{{ $t->patient_name }}</div>
                <div style="font-size: 12px; color: #555; margin: 4px 0 10px;">
                    Frame fitting & lens calculation
                </div>
                <form method="POST" action="{{ route('admin.queue.ticket.update', $t) }}" style="margin: 0;">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="completed">
                    <button type="submit" class="button button-quiet" style="font-size: 11px; padding: 5px 10px; width: 100%; border-color: #64BCB4;">
                        Complete Visit <i class="bx bx-check"></i>
                    </button>
                </form>
            </div>
            @empty
            <div style="text-align: center; color: var(--moa-slate); font-size: 13px; padding: 20px;">No patients dispensing.</div>
            @endforelse
        </div>

        <!-- Column 4: Completed / Resolved -->
        <div class="queue-col">
            <div class="queue-col-header">
                <span style="color: var(--moa-slate);">4. RECENTLY COMPLETED</span>
                <span class="queue-badge-count">{{ $completedTickets->count() }}</span>
            </div>
            @forelse($completedTickets as $t)
            <div class="queue-card" style="opacity: 0.85;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <span style="font-weight: 700; font-size: 13px;">{{ $t->ticket_number }} · {{ $t->patient_name }}</span>
                    <span class="record-status" style="font-size: 10px; padding: 2px 6px; {{ $t->status === 'no_show' ? 'background: #fee; color: #c00;' : '' }}">
                        {{ ucfirst(str_replace('_', ' ', $t->status)) }}
                    </span>
                </div>
                <small style="color: var(--moa-slate);">Resolved at {{ $t->completed_at ? $t->completed_at->format('g:i A') : 'earlier' }}</small>
            </div>
            @empty
            <div style="text-align: center; color: var(--moa-slate); font-size: 13px; padding: 20px;">No visits concluded yet.</div>
            @endforelse
        </div>
    </div>
</section>
@endsection

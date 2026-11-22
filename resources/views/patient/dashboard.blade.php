@extends('patient.layout')

@section('title', 'Patient Overview')

@section('content')
    <div class="patient-page-head overview-head">
        <div>
            <h1>Welcome back, <em>{{ $user->name }}</em></h1>
            <p>Keep your next appointment, verified prescriptions, eyewear orders, and visit summaries close at hand.</p>
        </div>
        <a class="button button-teal button-primary" href="{{ route('patient.appointments.create', [], false) }}">
            Book an appointment
        </a>
    </div>

    <!-- Active Eyewear Lab Order Tracker -->
    @if(isset($activeLabOrders) && $activeLabOrders->isNotEmpty())
        @foreach($activeLabOrders as $order)
        @php
            $stages = [
                'ordered' => 1,
                'in_edging_coating' => 2,
                'quality_check' => 3,
                'ready_for_pickup' => 4,
                'dispensed' => 5,
            ];
            $currentStage = $stages[$order->status] ?? 1;
        @endphp
        <section class="admin-panel" style="background: #fff; border: 1px solid #E5DFD7; border-radius: 12px; padding: 22px; margin-bottom: 24px; border-left: 4px solid var(--moa-teal);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
                <div>
                    <div class="eyebrow" style="color: var(--moa-bronze);">LIVE EYEWEAR FABRICATION TRACKER</div>
                    <h2 style="margin: 4px 0 2px; font-size: 20px;">Order #{{ $order->order_number }}</h2>
                    <p style="margin: 0; color: var(--moa-slate); font-size: 13px;">
                        {{ $order->frame ? $order->frame->brand.' '.$order->frame->model_name : 'Custom Eyewear' }}
                        with {{ $order->lensCatalog ? $order->lensCatalog->name : 'Prescription Lenses' }}
                    </p>
                </div>
                <div style="text-align: right;">
                    <span class="record-status" style="background: rgba(100, 188, 180, 0.2); color: #164e63; font-weight: 700;">
                        ● {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                    </span>
                    @if($order->estimated_completion_date)
                        <div style="font-size: 12px; color: var(--moa-slate); margin-top: 4px;">
                            Estimated Ready: <strong>{{ $order->estimated_completion_date->format('M d, Y') }}</strong>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Progress Bar Tracker -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin: 18px 0 10px;">
                <div style="text-align: center;">
                    <div style="height: 6px; border-radius: 3px; background: {{ $currentStage >= 1 ? 'var(--moa-teal)' : '#E2D9D0' }}; margin-bottom: 8px;"></div>
                    <div style="font-size: 11px; font-weight: 700; color: {{ $currentStage >= 1 ? 'var(--moa-dark)' : 'var(--moa-slate)' }};">1. Ordered</div>
                    <small style="font-size: 10px; color: var(--moa-slate);">Rx Received</small>
                </div>
                <div style="text-align: center;">
                    <div style="height: 6px; border-radius: 3px; background: {{ $currentStage >= 2 ? 'var(--moa-teal)' : '#E2D9D0' }}; margin-bottom: 8px;"></div>
                    <div style="font-size: 11px; font-weight: 700; color: {{ $currentStage >= 2 ? 'var(--moa-dark)' : 'var(--moa-slate)' }};">2. Edging & Tint</div>
                    <small style="font-size: 10px; color: var(--moa-slate);">Lab Fabrication</small>
                </div>
                <div style="text-align: center;">
                    <div style="height: 6px; border-radius: 3px; background: {{ $currentStage >= 3 ? 'var(--moa-teal)' : '#E2D9D0' }}; margin-bottom: 8px;"></div>
                    <div style="font-size: 11px; font-weight: 700; color: {{ $currentStage >= 3 ? 'var(--moa-dark)' : 'var(--moa-slate)' }};">3. Quality Check</div>
                    <small style="font-size: 10px; color: var(--moa-slate);">Optician Audit</small>
                </div>
                <div style="text-align: center;">
                    <div style="height: 6px; border-radius: 3px; background: {{ $currentStage >= 4 ? 'var(--moa-gold)' : '#E2D9D0' }}; margin-bottom: 8px;"></div>
                    <div style="font-size: 11px; font-weight: 700; color: {{ $currentStage >= 4 ? 'var(--moa-bronze)' : 'var(--moa-slate)' }};">4. Ready for Pickup</div>
                    <small style="font-size: 10px; color: var(--moa-slate);">Clinic Front-Desk</small>
                </div>
            </div>
        </section>
        @endforeach
    @endif

    <!-- Active In-Store Reservations -->
    @if(isset($activeReservations) && $activeReservations->isNotEmpty())
        <div style="background: rgba(240, 186, 127, 0.18); border: 1px solid var(--moa-gold); border-radius: 10px; padding: 14px 18px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <strong style="color: var(--moa-bronze);">In-Store Try-On Reservations Active:</strong>
                <span style="font-size: 13px; margin-left: 6px;">
                    @foreach($activeReservations as $res)
                        "{{ $res->frame->brand }} {{ $res->frame->model_name }}" (Held until {{ $res->reserved_until->format('M d, g:i A') }}){{ ! $loop->last ? ' · ' : '' }}
                    @endforeach
                </span>
            </div>
            <a href="{{ route('patient.eyewear') }}" class="button button-quiet" style="font-size: 11px; padding: 5px 12px;">Browse Eyewear <i class="bx bx-right-arrow-alt"></i></a>
        </div>
    @endif

    <section class="overview-next">
        <div>
            <h2>{{ $nextAppointment ? $nextAppointment->starts_at->format('l, M j') : 'Nothing scheduled yet' }}</h2>
            <p>{{ $nextAppointment ? $nextAppointment->service . ' · ' . $nextAppointment->starts_at->format('g:i A') . ($nextAppointment->specialist ? ' with '.$nextAppointment->specialist : '') : 'Choose a time when you are ready to see the doctor.' }}</p>
        </div>
        <a class="text-link" href="{{ route('patient.appointments.upcoming', [], false) }}">View appointments</a>
    </section>

    <div class="overview-grid">
        <a class="overview-card" href="{{ route('patient.appointments.upcoming', [], false) }}">
            <span class="overview-card-icon" aria-hidden="true"><i class="bx bx-time-five"></i></span>
            <strong>{{ $upcomingCount }}</strong>
            <span>Scheduled appointments</span>
            <b>View</b>
        </a>
        <a class="overview-card" href="{{ route('patient.prescriptions', [], false) }}">
            <span class="overview-card-icon" aria-hidden="true"><i class="bx bx-file-blank"></i></span>
            <strong>{{ $prescriptionCount }}</strong>
            <span>Prescriptions in vault</span>
            <b>Download</b>
        </a>
        <a class="overview-card" href="{{ route('patient.appointments.history', [], false) }}">
            <span class="overview-card-icon" aria-hidden="true"><i class="bx bx-history"></i></span>
            <strong>{{ $visitCount }}</strong>
            <span>Past visits & receipts</span>
            <b>History</b>
        </a>
    </div>

    <section class="overview-links">
        <div>
            <h2>Choose what you need today.</h2>
        </div>
        <div class="overview-link-list">
            <a href="{{ route('patient.eyewear', [], false) }}">
                <span>01</span>
                <strong>Explore eyewear showcase</strong>
                <b><i class="bx bx-right-arrow-alt"></i></b>
                <br><small>Filter frames by face shape & reserve in-store try-on</small>
            </a>    
            <a href="{{ route('patient.care-services', [], false) }}">
                <span>02</span>
                <strong>View care services & fees</strong>
                <b><i class="bx bx-right-arrow-alt"></i></b>
                <br><small>Routine exams, contact lens fittings, and glaucoma care</small>
            </a>
            <a href="{{ route('patient.prescriptions', [], false) }}">
                <span>03</span>
                <strong>Download signed prescription</strong>
                <b><i class="bx bx-right-arrow-alt"></i></b>
                <br><small>Verified copies with tamper-evident digital token</small>
            </a>
        </div>
    </section>
@endsection

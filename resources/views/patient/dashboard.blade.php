@extends('patient.layout')

@section('title', 'Patient Overview')

@section('content')
    <div class="patient-page-head overview-head">
        <div>
            <h1>Welcome back, <em>{{ \Illuminate\Support\Str::title(mb_strtolower(explode(' ', trim($user->name))[0])) }}</em></h1>
            <p>Keep your next appointment, verified prescriptions, eyewear orders, and visit summaries close at hand.</p>
        </div>
    </div>
    @if(session('success'))
        <p class="portal-feedback" role="status">{{ session('success') }}</p>
    @endif
    @if($errors->any())
        <div class="admin-validation" role="alert"><span>{{ $errors->first() }}</span></div>
    @endif

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
        </div>
    @endif

    <section class="overview-next">
        <div>
            <h2>{{ $nextAppointment ? $nextAppointment->starts_at->format('l, M j') : 'Nothing scheduled yet' }}</h2>
            <p>{{ $nextAppointment ? $nextAppointment->service . ' · ' . $nextAppointment->starts_at->format('g:i A') . ($nextAppointment->specialist ? ' with '.$nextAppointment->specialist : '') : 'Choose a time when you are ready to see the doctor.' }}</p>
            @if($nextAppointment)
                <p class="overview-next-meta"><i class="bx bx-map"></i> MOA Optical Clinic · Quezon City {{ $nextAppointment->exam_room ? "· Room ".$nextAppointment->exam_room : "" }}</p>
            @endif
        </div>
        @if($nextAppointment)
            @php
                $calStart = $nextAppointment->starts_at->copy()->utc();
                $calEnd = $calStart->copy()->addMinutes($nextAppointment->duration_minutes ?? 30);
                $calUrl = 'https://calendar.google.com/calendar/render?'.http_build_query([
                    'action' => 'TEMPLATE',
                    'text' => $nextAppointment->service.' at MOA Optical Clinic',
                    'dates' => $calStart->format('Ymd\THis\Z').'/'.$calEnd->format('Ymd\THis\Z'),
                    'details' => 'Appointment with '.($nextAppointment->specialist ?: 'the MOA care team'),
                    'location' => 'MOA Optical Clinic, Quezon City',
                ]);
            @endphp
            <div class="overview-next-actions">
                <a class="button button-teal button-small" href="{{ $calUrl }}" target="_blank" rel="noopener">Add to calendar</a>
                <a class="button button-quiet button-small" href="{{ route('patient.appointments.create', [], false) }}">Reschedule</a>
                <button class="text-link cancel-trigger" type="button" data-open-cancel>Cancel appointment</button>
                <a class="text-link" href="{{ route('patient.appointments.upcoming', [], false) }}">All appointments</a>
            </div>
        @else
            <a class="button button-teal button-small" href="{{ route('patient.appointments.create', [], false) }}">Book an appointment</a>
        @endif
    </section>

    <p class="overview-summary">
        @if($upcomingCount + $prescriptionCount + $visitCount === 0)
            Your records are empty for now. After your first eye exam, your verified prescription and visit receipts will appear here.
        @else
            You have {{ $upcomingCount }} upcoming {{ \Illuminate\Support\Str::plural('appointment', $upcomingCount) }},
            {{ $prescriptionCount }} {{ \Illuminate\Support\Str::plural('prescription', $prescriptionCount) }} on file and
            {{ $visitCount }} past {{ \Illuminate\Support\Str::plural('visit', $visitCount) }}.
            @if($prescriptionCount === 0) Your verified prescription will appear here after your first exam. @endif
        @endif
    </p>

@push('modals')
    @if($nextAppointment)
        <div class="modal-backdrop logout-modal-backdrop" data-cancel-modal hidden>
            <section class="modal logout-modal" role="dialog" aria-modal="true" aria-labelledby="cancel-title">
                <button class="modal-close" type="button" data-close-cancel aria-label="Close dialog">×</button>
                <h2 id="cancel-title">Cancel this appointment?</h2>
                <p>{{ $nextAppointment->service }} · {{ $nextAppointment->starts_at->format('l, M j · g:i A') }}. Please tell us why so we can improve.</p>
                <form method="POST" action="{{ route('appointments.cancel', $nextAppointment, false) }}" class="cancel-form">@csrf
                    @method('DELETE')
                    <label for="cancel-reason">Reason for cancelling</label>
                    <textarea id="cancel-reason" name="reason" required minlength="5" maxlength="500" rows="3" placeholder="At least 5 characters"></textarea>
                    <div class="logout-actions">
                        <button class="button button-quiet" type="button" data-close-cancel>Keep appointment</button>
                        <button class="button button-dark" type="submit">Confirm cancellation</button>
                    </div>
                </form>
            </section>
        </div>
    @endif
@endpush

@push('scripts')
    <script>
        (() => { const modal = document.querySelector('[data-cancel-modal]'); if (!modal) return; const trigger = document.querySelector('[data-open-cancel]'); const close = () => { modal.hidden = true; trigger?.focus(); }; trigger?.addEventListener('click', () => { modal.hidden = false; modal.querySelector('textarea')?.focus(); }); modal.querySelectorAll('[data-close-cancel]').forEach((b) => b.addEventListener('click', close)); modal.addEventListener('click', (e) => { if (e.target === modal) close(); }); document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.hidden) close(); }); })();
    </script>
@endpush
@endsection

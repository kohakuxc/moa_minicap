@extends('patient.layout')

@section('title', 'Upcoming appointments')

@section('content')
    <div class="patient-page-head compact-head">
        <div>
            <h1>Scheduled <em>appointments.</em></h1>
            <p>Keep track of what is next and make changes when you need to.</p>
        </div><a class="button button-teal" href="{{ route('patient.appointments.create', [], false) }}">Book another
            </a>
    </div>
    <section class="records-panel">@forelse($appointments as $appointment)
        <article class="appointment-record">
            <div class="appointment-date">
                <strong>{{ $appointment->starts_at->format('d') }}</strong><span>{{ $appointment->starts_at->format('M') }}</span>
            </div>
            <div class="appointment-details">
                <div class="eyebrow">{{ $appointment->starts_at->format('l · g:i A') }} · {{ $appointment->duration_minutes ?? 30 }} mins</div>
                <h2>{{ $appointment->service }}</h2>
                <p>{{ $appointment->specialist ? 'With ' . $appointment->specialist : 'MOA Care Team' }}
                    @if($appointment->exam_room) · Room: {{ $appointment->exam_room }}@endif
                    @if($appointment->notes) · {{ $appointment->notes }}@endif
                </p>
                @if($appointment->check_in_token)
                    <div style="margin-top: 6px; display: inline-flex; align-items: center; gap: 6px; font-size: 12px; background: rgba(100, 188, 180, 0.15); color: #2C6B65; border: 1px solid var(--moa-teal); padding: 3px 8px; border-radius: 4px; font-weight: 700;">
                        <span>Check-in Token:</span> <code>{{ $appointment->check_in_token }}</code>
                    </div>
                @endif
            </div><span
                class="record-status status-{{ $appointment->status }}">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span>@if(!in_array($appointment->status, ['cancelled', 'rejected']))
                    <details class="cancel-details">
                        <summary>Cancel</summary>
                        <form method="POST" action="{{ route('appointments.cancel', $appointment, false) }}">@csrf
                            @method('DELETE')<input name="reason" required placeholder="Reason"
                                aria-label="Cancellation reason"><button class="text-button" type="submit">Confirm
                                cancellation</button></form>
                </details>@endif
    </article>@empty<div class="records-empty"><span><i class="bx bx-calendar-x"></i></span>
            <h2>Your calendar is clear.</h2>
            <p>Book an appointment when you are ready to see the care team.</p><a class="button button-teal"
                href="{{ route('patient.appointments.create', [], false) }}">Book an appointment</a>
        </div>@endforelse
    </section>
@endsection
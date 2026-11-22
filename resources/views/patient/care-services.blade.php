@extends('patient.layout')

@section('title', 'Care Services')

@section('content')
<div class="patient-page-head">
    <div>
        <h1>Care that sees the <em>whole picture.</em></h1>
        <p>Thoughtful examinations and practical guidance, shaped around your eyes and your everyday life.</p>
    </div>
</div>

<div class="service-page-list">
    @forelse($services as $index => $service)
        <article>
            <span>{{ sprintf('%02d', $index + 1) }}</span>
            <div>
                <h2>{{ $service->name }}</h2>
                <p>{{ $service->description ?? 'Specialized optical care delivered by our certified optometrists and clinical team.' }}</p>
                <div style="margin-top: 8px; font-size: 13px; color: var(--moa-bronze); font-weight: 600; display: flex; gap: 16px; align-items: center;">
                    <span style="display: inline-flex; align-items: center; gap: 5px;"><i class="bx bx-time-five"></i> {{ $service->duration_minutes }} Minutes</span>
                    <span style="display: inline-flex; align-items: center; gap: 5px;"><i class="bx bx-purchase-tag"></i> ₱{{ number_format($service->fee, 2) }}</span>
                </div>
            </div>
            <a class="text-link" href="{{ route('patient.appointments.create', ['service_id' => $service->id], false) }}">
                Book this service
            </a>
        </article>
    @empty
        <article>
            <span>01</span>
            <div>
                <h2>Comprehensive eye exam</h2>
                <p>A full check of your vision and eye health, with time to ask questions and understand your current situation.</p>
            </div>
            <a class="text-link" href="{{ route('patient.appointments.create', [], false) }}">Book this service</a>
        </article>
        <article>
            <span>02</span>
            <div>
                <h2>Special case examination</h2>
                <p>Focused attention for a specific concern, a follow-up, or a change you have noticed in your vision.</p>
            </div>
            <a class="text-link" href="{{ route('patient.appointments.create', [], false) }}">Book this service</a>
        </article>
        <article>
            <span>03</span>
            <div>
                <h2>Frame styling</h2>
                <p>Personal guidance to help you find frames that suit your prescription, features, and way of life.</p>
            </div>
            <a class="text-link" href="{{ route('patient.appointments.create', [], false) }}">Book this service</a>
        </article>
    @endforelse
</div>
@endsection

@extends('patient.layout')

@section('title', 'Overview')

@section('content')
    <div class="patient-page-head overview-head">
        <div>
            <h1>Welcome back, <em>{{ $user->name }}</em></h1>
            <p>Keep your next appointment, records, and care options close at hand.</p>
        </div>
        <a class="button button-teal button-primary" href="{{ route('patient.appointments.create', [], false) }}">Book an
            appointment <span>→</span></a>
    </div>
    <section class="overview-next">
        <div><span class="eyebrow">NEXT APPOINTMENT</span>
            <h2>{{ $nextAppointment ? $nextAppointment->starts_at->format('l, M j') : 'Nothing scheduled yet' }}</h2>
            <p>{{ $nextAppointment ? $nextAppointment->service . ' · ' . $nextAppointment->starts_at->format('g:i A') : 'Choose a time when you are ready to see the doctor.' }}
            </p>
        </div>
        <a class="text-link" href="{{ route('patient.appointments.upcoming', [], false) }}">View appointments
            <span>→</span></a>
    </section>
    <div class="overview-grid">
        <a class="overview-card" href="{{ route('patient.appointments.upcoming', [], false) }}"><span
                class="overview-card-icon" aria-hidden="true">◷</span><strong>{{ $upcomingCount }}</strong><span>Upcoming
                appointments</span><b>View <span>→</span></b></a>
        <a class="overview-card" href="{{ route('patient.prescriptions', [], false) }}"><span class="overview-card-icon"
                aria-hidden="true">▤</span><strong>{{ $prescriptionCount }}</strong><span>Prescriptions on
                file</span><b>View <span>→</span></b></a>
        <a class="overview-card" href="{{ route('patient.appointments.history', [], false) }}"><span
                class="overview-card-icon" aria-hidden="true">↺</span><strong>{{ $visitCount }}</strong><span>Previous
                visits</span><b>View <span>→</span></b></a>
    </div>
    <section class="overview-links">
        <div>
            <h2>Choose what you need today.</h2>
        </div>
        <div class="overview-link-list" min-height="100px">
                <a href="{{ route('patient.eyewear', [], false) }}">
                    <span>01</span>
                        <strong>Explore eyewear</strong>
                            <b>→</b>
                            <br><small>See frames and find your style</small></br>
                                </a>    
                    
                    <a href="{{ route('patient.care-services', [], false) }}">
                        <span>02</span>
                            <strong>View care services</strong>
                                <b>→</b>
                                <br><small>Choose the right kind of visit</small></br>
                    </a>
                        
        </div>
    </section>
@endsection
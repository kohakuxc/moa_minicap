@extends('patient.layout')

@section('title', 'Overview')

@section('content')
<div class="patient-page-head overview-head">
    <div><div class="eyebrow">PATIENT PORTAL</div><h1>Good to see you, <em>{{ $user->name }}</em></h1><p>Your care, appointments, and eyewear preferences in one calm place.</p></div>
    <a class="button button-teal" href="{{ route('patient.appointments.create', [], false) }}">Book an appointment <span>→</span></a>
</div>
<section class="overview-next">
    <div><span class="eyebrow">NEXT UP</span><h2>{{ $nextAppointment ? $nextAppointment->starts_at->format('l, M j') : 'Nothing scheduled yet' }}</h2><p>{{ $nextAppointment ? $nextAppointment->service.' · '.$nextAppointment->starts_at->format('g:i A') : 'When you are ready, choose a time that works for you.' }}</p></div>
    <a class="text-link" href="{{ route('patient.appointments.upcoming', [], false) }}">View appointments <span>→</span></a>
</section>
<div class="overview-grid">
    <a class="overview-card" href="{{ route('patient.appointments.upcoming', [], false) }}"><span class="overview-card-icon">◷</span><span class="eyebrow">APPOINTMENTS</span><strong>{{ $upcomingCount }}</strong><small>Upcoming appointments</small><span class="card-arrow">→</span></a>
    <a class="overview-card" href="{{ route('patient.prescriptions', [], false) }}"><span class="overview-card-icon">▤</span><span class="eyebrow">PRESCRIPTIONS</span><strong>{{ $prescriptionCount }}</strong><small>Records on file</small><span class="card-arrow">→</span></a>
    <a class="overview-card" href="{{ route('patient.appointments.history', [], false) }}"><span class="overview-card-icon">↺</span><span class="eyebrow">VISITS</span><strong>{{ $visitCount }}</strong><small>Previous visits</small><span class="card-arrow">→</span></a>
</div>
<section class="overview-links"><div><div class="eyebrow">EXPLORE MOA</div><h2>Find the right place to begin.</h2></div><div class="overview-link-list"><a href="{{ route('patient.eyewear', [], false) }}"><span>01</span><strong>Explore eyewear</strong><small>Find a style that feels like you</small><b>→</b></a><a href="{{ route('patient.care-services', [], false) }}"><span>02</span><strong>Learn about care</strong><small>See what your next visit can include</small><b>→</b></a></div></section>
@endsection
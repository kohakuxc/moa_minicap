@extends('patient.layout')

@section('title', 'Care services')

@section('content')
<div class="patient-page-head"><div><h1>Care that sees the <em>whole picture.</em></h1><p>Thoughtful examinations and practical guidance, shaped around your eyes and your everyday life.</p></div></div>
<div class="service-page-list"><article><span>01</span><div><h2>Comprehensive eye exam</h2><p>A full check of your vision and eye health, with time to ask questions and understand your current situation.</p></div><a class="text-link" href="{{ route('patient.appointments.create', [], false) }}">Book this service <span>→</span></a></article><article><span>02</span><div><h2>Special case examination</h2><p>Focused attention for a specific concern, a follow-up, or a change you have noticed in your vision.</p></div><a class="text-link" href="{{ route('patient.appointments.create', [], false) }}">Book this service <span>→</span></a></article><article><span>03</span><div><h2>Frame styling</h2><p>Personal guidance to help you find frames that suit your prescription, features, and way of life.</p></div><a class="text-link" href="{{ route('patient.appointments.create', [], false) }}">Book this service <span>→</span></a></article></div>
@endsection 
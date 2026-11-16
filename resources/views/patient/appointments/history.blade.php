@extends('patient.layout')

@section('title', 'Visit history')

@section('content')
<div class="patient-page-head"><div><h1>Visit <em>history.</em></h1><p>A simple record of the care you have received with MOA.</p></div></div>
<section class="records-panel history-panel">@forelse($appointments as $appointment)<article class="history-record"><div class="history-date"><strong>{{ $appointment->starts_at->format('M') }}</strong><span>{{ $appointment->starts_at->format('j, Y') }}</span></div><div><div class="eyebrow">{{ $appointment->starts_at->format('g:i A') }}</div><h2>{{ $appointment->service }}</h2><p>{{ $appointment->specialist ? 'With '.$appointment->specialist : 'MOA care team' }}@if($appointment->reason) · {{ $appointment->reason }}@endif</p></div><span class="record-status status-{{ $appointment->status }}">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span></article>@empty<div class="records-empty"><span>↺</span><h2>No previous visits yet.</h2><p>Your completed appointments will be listed here.</p></div>@endforelse</section>
@endsection
@extends('patient.layout')

@section('title', 'Prescriptions')

@section('content')
<div class="patient-page-head"><div><div class="eyebrow">YOUR RECORDS</div><h1>Prescription <em>history.</em></h1><p>Your recorded lens details, kept together for easy reference.</p></div></div>
<section class="records-panel prescription-panel">@forelse($prescriptions as $prescription)<article class="prescription-record"><div class="prescription-eye">{{ $prescription->eye }}</div><div><div class="eyebrow">ISSUED {{ $prescription->issued_at->format('M j, Y') }}</div><h2>{{ ucfirst($prescription->eye) }} prescription</h2></div><dl><div><dt>Sphere</dt><dd>{{ $prescription->sphere ?? '—' }}</dd></div><div><dt>Cylinder</dt><dd>{{ $prescription->cylinder ?? '—' }}</dd></div><div><dt>Axis</dt><dd>{{ $prescription->axis ?? '—' }}</dd></div><div><dt>PD</dt><dd>{{ $prescription->pd ?? '—' }}</dd></div></dl></article>@empty<div class="records-empty"><span>▤</span><h2>No prescriptions on file.</h2><p>Your prescription details will appear here after your care team records them.</p></div>@endforelse</section>
@endsection
@extends('patient.layout')

@section('title', 'Eyewear')

@section('content')
<div class="patient-page-head"><div><div class="eyebrow">YOUR STYLE</div><h1>Choose your <em>eyewear.</em></h1><p>Every frame should feel like a natural extension of you. Start with what you need today.</p></div></div>
<div class="eyewear-page-grid"><a class="eyewear-choice eyewear-choice-featured" href="{{ route('patient.appointments.create', [], false) }}"><span class="eyewear-symbol">◉</span><div><div class="eyebrow">01 · EVERYDAY PROTECTION</div><h2>Sunglasses</h2><p>Protection with signature style, made for bright days and easy confidence.</p></div><b>Explore a fitting <span>→</span></b></a><a class="eyewear-choice" href="{{ route('patient.appointments.create', [], false) }}"><span class="eyewear-symbol">◌</span><div><div class="eyebrow">02 · CLOSE-UP COMFORT</div><h2>Reading glasses</h2><p>Comfort for the details that matter, from menus to long-form reading.</p></div><b>Talk to the team <span>→</span></b></a><a class="eyewear-choice" href="{{ route('patient.appointments.create', [], false) }}"><span class="eyewear-symbol">✧</span><div><div class="eyebrow">03 · CLEARER VISION</div><h2>Prescription glasses</h2><p>Graded lenses tailored to your prescription, routine, and personal style.</p></div><b>Find your fit <span>→</span></b></a></div>
@endsection
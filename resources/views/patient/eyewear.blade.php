@extends('patient.layout')

@section('title', 'Eyewear')

@section('content')
    <div class="patient-page-head">
        <div>
            <h1>Find frames that fit <em>your life.</em></h1>
            <p>Start with the kind of clarity or protection you want, then let our team help you find the right fit.</p>
        </div>
    </div>

    <div class="eyewear-page-grid">
        <a class="eyewear-choice" href="{{ route('patient.appointments.create', [], false) }}"><img
                src="/asset/img/prescription.png" alt="prescription">
            <div>
                <h2>Prescription glasses</h2>
                <p>Graded lenses tailored to your prescription, routine, and personal style.</p>
            </div><b>Book a fitting <span>→</span></b>
        </a><a class="eyewear-choice" href="{{ route('patient.appointments.create', [], false) }}"><img
                src="/asset/img/sunglasses.png" alt="sunglasses">
            <div>
                <h2>Sunglasses</h2>
                <p>Protection with signature style, made for bright days and easy confidence.</p>
            </div><b>Explore a fitting <span>→</span></b>
        </a><a class="eyewear-choice" href="{{ route('patient.appointments.create', [], false) }}"><img
                src="/asset/img/reading.png" alt="reading">
            <div>
                <h2>Reading glasses</h2>
                <p>Comfort for the details that matter, from menus to long-form reading.</p>
            </div><b>Talk to the team <span>→</span></b>
        </a>
    </div>
@endsection
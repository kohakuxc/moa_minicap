@extends('patient.layout')

@section('title', 'Visit History & Invoices')

@section('content')
<div class="patient-page-head">
    <div>
        <h1>Visit history & <em>reimbursement receipts.</em></h1>
        <p>A complete record of your previous optical examinations, itemized billing receipts for HMO claims, and one-click annual exam rebooking.</p>
    </div>
</div>

<section class="records-panel history-panel">
    @forelse($appointments as $appointment)
    <article class="history-record" style="position: relative; display: flex; flex-direction: column; justify-content: space-between;">
        <div style="display: flex; gap: 16px; align-items: flex-start;">
            <div class="history-date">
                <strong>{{ $appointment->starts_at->format('M') }}</strong>
                <span>{{ $appointment->starts_at->format('j, Y') }}</span>
            </div>
            <div style="flex: 1;">
                <div class="eyebrow">
                    {{ $appointment->starts_at->format('g:i A') }} ({{ $appointment->duration_minutes }} min)
                </div>
                <h2>{{ $appointment->service }}</h2>
                <p>
                    {{ $appointment->specialist ? 'Specialist: '.$appointment->specialist : 'MOA Optometry Team' }}
                    @if($appointment->exam_room) · Station: {{ $appointment->exam_room }} @endif
                    @if($appointment->reason) · <em>"{{ $appointment->reason }}"</em> @endif
                </p>

                <!-- Itemized Invoice / HMO Receipt Breakdown -->
                @if($appointment->invoices && $appointment->invoices->isNotEmpty())
                    @foreach($appointment->invoices as $inv)
                    <div style="background: #FAF7F2; border: 1px solid #EBE4DC; border-radius: 6px; padding: 10px 14px; margin-top: 10px; font-size: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <strong>Official Receipt: {{ $inv->invoice_number }}</strong>
                            <span class="record-status" style="font-size: 10px; padding: 2px 6px;">{{ ucfirst($inv->payment_status) }}</span>
                        </div>
                        <div style="color: #555;">
                            Payment: {{ ucfirst($inv->payment_method) }} · Total Paid: <strong>₱{{ number_format($inv->patient_payable_amount, 2) }}</strong>
                            @if($inv->hmo_covered_amount > 0)
                                · HMO Covered: <strong>₱{{ number_format($inv->hmo_covered_amount, 2) }}</strong>
                            @endif
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>

            <span class="record-status status-{{ $appointment->status }}">
                {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
            </span>
        </div>

        <div style="border-top: 1px solid #EFEAE3; padding-top: 12px; margin-top: 14px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 11px; color: var(--moa-slate);">
                Completed on {{ $appointment->starts_at->format('M d, Y') }}
            </span>
            <a href="{{ route('patient.appointments.create') }}?service_id={{ $appointment->service_id }}" class="button button-quiet" style="font-size: 11px; padding: 6px 14px; border-color: var(--moa-bronze); color: var(--moa-bronze); display: inline-flex; align-items: center; gap: 4px;">
                <i class="bx bx-refresh"></i> Book Next Annual Exam
            </a>
        </div>
    </article>
    @empty
    <div class="records-empty">
        <span><i class="bx bx-history"></i></span>
        <h2>No previous visits yet.</h2>
        <p>Your completed appointments, diagnosis summaries, and reimbursement receipts will be cataloged here.</p>
    </div>
    @endforelse
</section>
@endsection

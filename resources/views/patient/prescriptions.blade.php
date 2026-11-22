@extends('patient.layout')

@section('title', 'Prescription Vault')

@section('content')
<div class="patient-page-head">
    <div>
        <div class="eyebrow" style="color: var(--moa-bronze);">OPTICAL VAULT</div>
        <h1>Prescription <em>history & documents.</em></h1>
        <p>Your officially signed refractive records, available for in-clinic dispensing or external pharmacy download.</p>
    </div>
</div>

<section class="records-panel prescription-panel">
    @forelse($prescriptions as $prescription)
    <article class="prescription-record" style="position: relative; display: flex; flex-direction: column; justify-content: space-between;">
        <div style="display: flex; gap: 14px; align-items: flex-start;">
            <div class="prescription-eye">{{ $prescription->eye }}</div>
            <div style="flex: 1;">
                <div class="eyebrow">
                    ISSUED {{ $prescription->issued_at->format('M j, Y') }} · {{ strtoupper($prescription->rx_type ?? 'spectacle') }}
                </div>
                <h2>{{ ucfirst($prescription->eye) }} prescription</h2>
                @if($prescription->signature_token)
                    <div style="font-size: 11px; color: var(--moa-teal); font-weight: 600; margin-top: 2px; display: inline-flex; align-items: center; gap: 4px;">
                        <i class="bx bx-check-shield"></i> Digitally Signed & Verified
                    </div>
                @endif
            </div>
        </div>

        <dl style="margin: 16px 0;">
            <div><dt>Sphere</dt><dd>{{ $prescription->sphere ? sprintf('%+.2f', $prescription->sphere) : 'PLANO' }}</dd></div>
            <div><dt>Cylinder</dt><dd>{{ $prescription->cylinder ? sprintf('%+.2f', $prescription->cylinder) : 'SPH' }}</dd></div>
            <div><dt>Axis</dt><dd>{{ $prescription->axis ? $prescription->axis.'°' : '—' }}</dd></div>
            <div><dt>Add</dt><dd>{{ $prescription->add ? sprintf('%+.2f', $prescription->add) : '—' }}</dd></div>
            <div><dt>PD</dt><dd>{{ $prescription->pd ? $prescription->pd.' mm' : '—' }}</dd></div>
        </dl>

        <div style="border-top: 1px solid #EFEAE3; padding-top: 12px; margin-top: 8px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 11px; color: var(--moa-slate);">
                Valid through {{ $prescription->expires_at ? $prescription->expires_at->format('M Y') : '2 Years' }}
            </span>
            <a href="{{ route('patient.prescriptions.print', $prescription) }}" target="_blank" class="button button-quiet" style="font-size: 11px; padding: 5px 12px; border-color: var(--moa-bronze); color: var(--moa-bronze); display: inline-flex; align-items: center; gap: 4px;">
                <i class="bx bx-printer"></i> Print / PDF <i class="bx bx-link-external"></i>
            </a>
        </div>
    </article>
    @empty
    <div class="records-empty">
        <span><i class="bx bx-file-blank"></i></span>
        <h2>No prescriptions on file.</h2>
        <p>Your optical prescription details will appear here after your comprehensive eye examination.</p>
    </div>
    @endforelse
</section>
@endsection

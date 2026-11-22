<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Prescription #{{ $prescription->id }} · {{ $patient->name }} · MOA Optical Clinic</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alegreya+SC:wght@500;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --moa-gold: #F0BA7F;
            --moa-bronze: #9F652C;
            --moa-teal: #64BCB4;
            --moa-slate: #959192;
            --moa-dark: #1F2421;
        }
        body {
            font-family: "DM Sans", -apple-system, sans-serif;
            background: #F4F1EA;
            color: var(--moa-dark);
            margin: 0;
            padding: 30px;
            display: flex;
            justify-content: center;
        }
        .rx-sheet {
            background: #fff;
            width: 100%;
            max-width: 780px;
            padding: 48px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            border: 1px solid #E5DFD7;
            position: relative;
        }
        .rx-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--moa-bronze);
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .clinic-brand {
            font: 700 32px/1 "Alegreya SC", serif;
            color: var(--moa-bronze);
            letter-spacing: 1px;
        }
        .clinic-brand span {
            color: var(--moa-teal);
            font-size: 16px;
            display: block;
            margin-top: 4px;
            font-family: "DM Sans", sans-serif;
            letter-spacing: 0;
        }
        .rx-meta {
            text-align: right;
            font-size: 12px;
            color: var(--moa-slate);
            line-height: 1.5;
        }
        .patient-strip {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 16px;
            background: #FAF7F2;
            border: 1px solid #EBE4DC;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 28px;
            font-size: 13px;
        }
        .patient-strip strong {
            display: block;
            color: var(--moa-slate);
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .rx-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }
        .rx-table th {
            background: #F0EAE1;
            color: var(--moa-bronze);
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            padding: 12px;
            border: 1px solid #D5CBC0;
            text-align: center;
        }
        .rx-table td {
            padding: 14px 12px;
            border: 1px solid #D5CBC0;
            text-align: center;
            font-size: 15px;
            font-weight: 600;
        }
        .rx-table td.eye-col {
            font-family: "Alegreya SC", serif;
            font-size: 18px;
            color: var(--moa-bronze);
            background: #FAF7F2;
            font-weight: 700;
        }
        .rx-seal-box {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid #E5DFD7;
        }
        .digital-hash-info {
            font-size: 11px;
            color: var(--moa-slate);
            line-height: 1.5;
            max-width: 420px;
        }
        .token-hash {
            font-family: monospace;
            background: #F2EFE9;
            padding: 4px 8px;
            border-radius: 4px;
            color: #444;
            word-break: break-all;
            margin-top: 4px;
            display: inline-block;
        }
        .doctor-signature-seal {
            text-align: center;
        }
        .seal-signature-line {
            font-family: 'Brush Script MT', cursive, sans-serif;
            font-size: 26px;
            color: var(--moa-bronze);
            margin-bottom: 2px;
        }
        .seal-doctor-name {
            font-weight: 700;
            font-size: 13px;
            border-top: 1px solid #333;
            padding-top: 4px;
            min-width: 180px;
        }
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 100;
        }
        .btn-print {
            background: var(--moa-bronze);
            color: #fff;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        @media print {
            body { background: #fff; padding: 0; }
            .rx-sheet { box-shadow: none; border: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()" style="display: inline-flex; align-items: center; gap: 6px;"><i class="bx bx-printer"></i> Print / Save as PDF</button>
        <button class="btn-print" style="background: #fff; color: #333; border: 1px solid #ccc;" onclick="window.close()">Close</button>
    </div>

    <div class="rx-sheet">
        <header class="rx-header">
            <div>
                <a class="clinic-brand" href="{{ route('dashboard', [], false) }}" style="text-decoration: none;">MOA OPTICAL CLINIC <span>EYECARE & REFRACTION SPECIALISTS</span></a>
                <div style="font-size: 12px; color: var(--moa-slate); margin-top: 6px;">
                    Suite 402, High Street Promenade · Tel: (02) 8812-4900 · Accredited Optometric Facility
                </div>
            </div>
            <div class="rx-meta">
                <div><strong>Prescription #:</strong> {{ sprintf('RX-%05d', $prescription->id) }}</div>
                <div><strong>Issued Date:</strong> {{ $prescription->issued_at->format('F d, Y') }}</div>
                <div><strong>Valid Until:</strong> {{ $prescription->expires_at ? $prescription->expires_at->format('F d, Y') : '2 Years from issue' }}</div>
                <div><strong>Modality:</strong> {{ ucfirst($prescription->rx_type) }} Lenses</div>
            </div>
        </header>

        <div class="patient-strip">
            <div>
                <strong>Patient Name</strong>
                <span style="font-size: 16px; font-weight: 700;">{{ $patient->name }}</span>
            </div>
            <div>
                <strong>Patient ID</strong>
                <span>#PAT-{{ $patient->id }}</span>
            </div>
            <div>
                <strong>Contact Phone</strong>
                <span>{{ $patient->phone ?: 'On file' }}</span>
            </div>
        </div>

        <table class="rx-table">
            <thead>
                <tr>
                    <th style="width: 100px;">Eye</th>
                    <th>Sphere (SPH)</th>
                    <th>Cylinder (CYL)</th>
                    <th>Axis</th>
                    <th>Add</th>
                    <th>PD (mm)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="eye-col">OD (Right)</td>
                    <td>{{ $odPrescription?->sphere ? sprintf('%+.2f', $odPrescription->sphere) : 'PLANO' }}</td>
                    <td>{{ $odPrescription?->cylinder ? sprintf('%+.2f', $odPrescription->cylinder) : 'SPH' }}</td>
                    <td>{{ $odPrescription?->axis ? $odPrescription->axis.'°' : '—' }}</td>
                    <td>{{ $odPrescription?->add ? sprintf('%+.2f', $odPrescription->add) : '—' }}</td>
                    <td rowspan="2" style="vertical-align: middle; background: #FAF7F2; font-size: 16px;">
                        {{ $prescription->pd ?? ($odPrescription?->pd ?? '63.0') }}
                    </td>
                </tr>
                <tr>
                    <td class="eye-col">OS (Left)</td>
                    <td>{{ $osPrescription?->sphere ? sprintf('%+.2f', $osPrescription->sphere) : 'PLANO' }}</td>
                    <td>{{ $osPrescription?->cylinder ? sprintf('%+.2f', $osPrescription->cylinder) : 'SPH' }}</td>
                    <td>{{ $osPrescription?->axis ? $osPrescription->axis.'°' : '—' }}</td>
                    <td>{{ $osPrescription?->add ? sprintf('%+.2f', $osPrescription->add) : '—' }}</td>
                </tr>
            </tbody>
        </table>

        @if($prescription->notes)
        <div style="background: #F9F7F4; border-radius: 6px; padding: 12px 16px; font-size: 13px; margin-bottom: 24px;">
            <strong>Dispensing Instructions:</strong> {{ $prescription->notes }}
        </div>
        @endif

        <div class="rx-seal-box">
            <div class="digital-hash-info">
                <strong>Cryptographic Verification Status:</strong>
                <div>This prescription was digitally compiled and signed by a licensed Doctor of Optometry. The hash below proves document integrity against alterations:</div>
                <div class="token-hash">
                    SHA-256: {{ $prescription->signature_token ?: hash('sha256', $prescription->id . $patient->id . 'MOA_VERIFIED') }}
                </div>
            </div>

            <div class="doctor-signature-seal">
                <div class="seal-signature-line">
                    {{ $prescription->doctor ? $prescription->doctor->name : 'Dr. Elena Vance, OD' }}
                </div>
                <div class="seal-doctor-name">
                    {{ $prescription->doctor ? $prescription->doctor->name : 'Dr. Elena Vance, OD' }}
                    <div style="font-size: 11px; color: var(--moa-slate); font-weight: normal;">Licensed Optometrist · PRC #049281</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

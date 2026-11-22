<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta http-equiv="refresh" content="15">
    <title>Waiting Room Queue Display · MOA Optical Clinic</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alegreya+SC:wght@500;700&family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/app.css">
    <style>
        body {
            background: #191E24;
            color: #F7FAFC;
            font-family: "DM Sans", sans-serif;
            margin: 0;
            padding: 24px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }
        .display-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #2D3748;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .display-brand {
            font: 700 36px/1 "Alegreya SC", serif;
            color: #F0BA7F;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .display-brand span {
            color: #64BCB4;
            font-size: 20px;
            letter-spacing: 2px;
        }
        .clock-display {
            font: 700 28px/1 monospace;
            color: #E2E8F0;
        }
        .tv-grid {
            display: grid;
            grid-template-columns: 2fr 1.2fr;
            gap: 24px;
            flex: 1;
        }
        .tv-panel {
            background: #232932;
            border: 1px solid #323B47;
            border-radius: 16px;
            padding: 24px;
            display: flex;
            flex-direction: column;
        }
        .tv-panel-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #A0AEC0;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .calling-card {
            background: linear-gradient(135deg, rgba(159, 101, 44, 0.25), rgba(100, 188, 180, 0.15));
            border: 2px solid #64BCB4;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 24px rgba(0,0,0,0.25);
            animation: pulse-border 2s infinite alternate;
        }
        @keyframes pulse-border {
            from { border-color: #64BCB4; }
            to { border-color: #F0BA7F; }
        }
        .calling-token {
            font: 700 52px/1 "Alegreya SC", serif;
            color: #F0BA7F;
            letter-spacing: 2px;
        }
        .calling-patient {
            font-size: 22px;
            font-weight: 700;
            color: #FFFFFF;
            margin-top: 4px;
        }
        .calling-room {
            font-size: 20px;
            font-weight: 700;
            color: #64BCB4;
            background: #1A202C;
            padding: 10px 18px;
            border-radius: 8px;
            border: 1px solid #4A5568;
            text-align: right;
        }
        .upcoming-token-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: #1A202C;
            border: 1px solid #2D3748;
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .upcoming-token-badge {
            font: 700 24px/1 "Alegreya SC", serif;
            color: #F0BA7F;
        }
        .dispensing-badge {
            color: #F0BA7F;
            font-weight: 700;
        }
        .tv-footer {
            margin-top: 20px;
            border-top: 1px solid #2D3748;
            padding-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #718096;
        }
    </style>
</head>
<body>
    <header class="display-header">
        <a class="display-brand" href="{{ route('admin.dashboard', [], false) }}" style="text-decoration: none;">
            MOA OPTICAL CLINIC <span>PATIENT FLOW SYSTEM</span>
        </a>
        <div class="clock-display" id="clock">
            {{ now()->format('h:i:s A') }}
        </div>
    </header>

    <div class="tv-grid">
        <!-- Main: Currently Being Served -->
        <div class="tv-panel">
            <div class="tv-panel-title">
                <span>NOW CALLING / IN CONSULTATION</span>
                <span style="color: #64BCB4;">● LIVE</span>
            </div>

            @forelse($inConsultation as $c)
            <div class="calling-card">
                <div>
                    <div class="calling-token">{{ $c->ticket_number }}</div>
                    <div class="calling-patient">{{ $c->patient_name }}</div>
                </div>
                <div class="calling-room">
                    <div>PROCEED TO</div>
                    <div style="font-size: 24px; color: #FFFFFF;">{{ $c->assigned_room ?: 'Exam Room 1' }}</div>
                </div>
            </div>
            @empty
            <div style="padding: 40px; text-align: center; color: #718096; font-size: 20px;">
                All consultation rooms ready. Next patient ticket will appear here.
            </div>
            @endforelse

            @if($dispensing->isNotEmpty())
            <div style="margin-top: 20px;">
                <div class="tv-panel-title">
                    <span>FRAME BAR & LENS DISPENSING COUNTER</span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                    @foreach($dispensing as $d)
                    <div style="background: #1A202C; border: 1px solid #9F652C; border-radius: 8px; padding: 12px 16px;">
                        <span class="dispensing-badge" style="font-size: 20px; font-family: 'Alegreya SC', serif;">{{ $d->ticket_number }}</span>
                        <div style="font-weight: 600; font-size: 14px; margin-top: 4px;">{{ $d->patient_name }}</div>
                        <small style="color: #64BCB4;">At Optical Counter</small>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Side: Next In Line / Waiting -->
        <div class="tv-panel">
            <div class="tv-panel-title">
                <span>NEXT IN LINE (WAITING LOUNGE)</span>
                <span>{{ $waiting->count() }} Waiting</span>
            </div>

            @forelse($waiting as $w)
            <div class="upcoming-token-row">
                <span class="upcoming-token-badge">{{ $w->ticket_number }}</span>
                <span style="font-weight: 600;">{{ $w->patient_name }}</span>
                <small style="color: #A0AEC0;">Checked in {{ $w->check_in_time->format('g:i A') }}</small>
            </div>
            @empty
            <div style="padding: 24px; text-align: center; color: #718096;">
                No patients currently waiting in queue.
            </div>
            @endforelse
        </div>
    </div>

    <footer class="tv-footer">
        <div>Please have your ticket number ready. Our clinical team will announce your token when your diagnostic suite is prepared.</div>
        <div>Auto-refreshes every 15 seconds</div>
    </footer>

    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString();
        }
        setInterval(updateClock, 1000);
    </script>
</body>
</html>

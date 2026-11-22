@extends('admin.layout')

@section('title', 'Financial & HMO Governance')

@section('content')
<div class="admin-page-head">
    <div>
        <h1>Clinic billing & <em>HMO claims.</em></h1>
        <p>End-of-day register reconciliation, HMO insurance provider accreditation, claim approvals, and refund authorizations.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <form method="POST" action="{{ route('admin.billing.reconcile') }}" style="margin: 0;" onsubmit="return confirm('Execute end-of-day financial reconciliation? This balances all paid transactions for today.');">
            @csrf
            <button class="button button-teal" type="submit">
                <i class="bx bx-check-double"></i> Reconcile & Balance Day
            </button>
        </form>
    </div>
</div>

@if(session('success'))
    <p class="portal-feedback" role="status" style="border-left: 4px solid var(--moa-teal); background: rgba(100, 188, 180, 0.12); color: #164e63;">
        <i class="bx bx-check-circle"></i> {{ session('success') }}
    </p>
@endif

@if($errors->any())
    <div class="admin-validation" role="alert">
        <strong>Check financial inputs:</strong>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<!-- Daily Financial KPI Cards -->
<div class="kpi-grid-5" style="margin-bottom: 24px;">
    <div class="kpi-card-custom kpi-revenue">
        <div class="kpi-header"><span>Today's Total Paid</span><i class="bx bx-dollar-circle"></i></div>
        <div class="kpi-value">₱{{ number_format($todayStats['total'], 2) }}</div>
        <div class="kpi-sub">Cleared collections today</div>
    </div>
    <div class="kpi-card-custom kpi-appointments">
        <div class="kpi-header"><span>Cash Counter</span><i class="bx bx-money"></i></div>
        <div class="kpi-value">₱{{ number_format($todayStats['cash'], 2) }}</div>
        <div class="kpi-sub">Cash on drawer</div>
    </div>
    <div class="kpi-card-custom kpi-lab">
        <div class="kpi-header"><span>Card & Online</span><i class="bx bx-credit-card"></i></div>
        <div class="kpi-value">₱{{ number_format($todayStats['card'], 2) }}</div>
        <div class="kpi-sub">Credit/Debit & Digital POS</div>
    </div>
    <div class="kpi-card-custom kpi-noshow">
        <div class="kpi-header"><span>HMO Allowance Covered</span><i class="bx bx-shield-quarter"></i></div>
        <div class="kpi-value">₱{{ number_format($todayStats['hmo'], 2) }}</div>
        <div class="kpi-sub">{{ $todayStats['pending_claims_count'] }} pending claims to submit</div>
    </div>
    <div class="kpi-card-custom kpi-inventory">
        <div class="kpi-header"><span>Unreconciled Invoices</span><i class="bx bx-scale"></i></div>
        <div class="kpi-value">{{ $todayStats['unreconciled_count'] }}</div>
        <div class="kpi-sub">Awaiting end-of-day register closure</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 24px; margin-bottom: 24px;">
    <!-- 1. HMO Claims Pipeline -->
    <section class="admin-panel" style="margin-top: 0;">
        <div class="admin-section-head">
            <div>
                <h2>HMO provider claims</h2>
                <p>Process health maintenance organization claim approvals and reimbursements.</p>
            </div>
        </div>

        <div class="admin-record-list">
            @forelse($hmoClaims as $claim)
            <div class="admin-record" style="display: flex; justify-content: space-between; align-items: flex-start; padding: 14px 16px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="ticket-badge" style="font-size: 11px;">{{ $claim->claim_number }}</span>
                        <strong>{{ $claim->hmoProvider->name }}</strong>
                    </div>
                    <div style="font-size: 13px; color: #444; margin-top: 4px;">
                        Patient: <strong>{{ $claim->patient->name }}</strong> (Member ID: {{ $claim->member_id }})
                        <br>
                        Claimed: <strong style="color: var(--moa-bronze);">₱{{ number_format($claim->claimed_amount, 2) }}</strong>
                        @if($claim->approved_amount > 0)
                            · Approved: <strong style="color: #164e63;">₱{{ number_format($claim->approved_amount, 2) }}</strong> (Code: {{ $claim->approval_code }})
                        @endif
                    </div>
                </div>

                <div style="text-align: right;">
                    <span class="record-status" style="font-size: 10px; margin-bottom: 6px; display: inline-block;
                        @if($claim->status === 'approved') background: rgba(100, 188, 180, 0.2); color: #164e63;
                        @elseif($claim->status === 'rejected') background: rgba(224, 86, 76, 0.2); color: #C53030;
                        @else background: rgba(240, 186, 127, 0.25); color: #7A5530;
                        @endif">
                        {{ ucfirst($claim->status) }}
                    </span>

                    <form method="POST" action="{{ route('admin.billing.claims.status', $claim) }}" style="display: flex; gap: 4px; justify-content: flex-end; margin: 0;">
                        @csrf
                        @method('PATCH')
                        <select name="status" style="padding: 4px 6px; border: 1px solid #D5CBC0; border-radius: 4px; font-size: 11px;">
                            <option value="pending" @selected($claim->status === 'pending')>Pending</option>
                            <option value="submitted" @selected($claim->status === 'submitted')>Submitted</option>
                            <option value="approved" @selected($claim->status === 'approved')>Approved</option>
                            <option value="rejected" @selected($claim->status === 'rejected')>Rejected</option>
                        </select>
                        <button type="submit" class="button button-quiet" style="font-size: 10px; padding: 4px 8px;">
                            Save
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="admin-empty">No HMO claims pending.</div>
            @endforelse
        </div>
        {{ $hmoClaims->links() }}
    </section>

    <!-- 2. Accredited HMO Partners -->
    <section class="admin-panel" style="margin-top: 0;">
        <div class="admin-section-head">
            <div>
                <h2>HMO providers catalog</h2>
                <p>Register accredited insurance networks and claim agreement terms.</p>
            </div>
            <details style="position: relative;">
                <summary class="button button-quiet" style="font-size: 12px; cursor: pointer;"><i class="bx bx-plus"></i> Add Provider</summary>
                <div style="position: absolute; right: 0; top: 100%; width: 340px; background: #fff; border: 1px solid #E5DFD7; border-radius: 8px; padding: 18px; box-shadow: 0 8px 24px rgba(0,0,0,0.12); z-index: 20; margin-top: 6px;">
                    <form method="POST" action="{{ route('admin.billing.hmo.store') }}" style="display: flex; flex-direction: column; gap: 8px;">
                        @csrf
                        <div style="font-weight: 700; font-size: 13px; margin-bottom: 4px;">Accredit HMO Provider</div>
                        <input type="text" name="name" placeholder="Provider Name (e.g. Cigna)" required style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                        <input type="text" name="code" placeholder="Code (e.g. CIGNA_PH)" required style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                        <input type="email" name="contact_email" placeholder="Claims Liaison Email" style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                        <input type="text" name="contact_phone" placeholder="Contact Phone" style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                        <textarea name="coverage_terms" rows="2" placeholder="Optical package coverage rules..." style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px; font-family: inherit;"></textarea>
                        <button type="submit" class="button button-teal" style="font-size: 12px; margin-top: 4px;">Accredit Partner</button>
                    </form>
                </div>
            </details>
        </div>

        <div class="admin-record-list">
            @foreach($hmoProviders as $provider)
            <div class="admin-record" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px;">
                <div>
                    <strong>{{ $provider->name }}</strong>
                    <span class="staff-role-badge">{{ $provider->code }}</span>
                    <p style="margin: 3px 0 0; font-size: 12px; color: var(--moa-slate);">
                        {{ $provider->claims_count }} historical claims · Contact: {{ $provider->contact_phone ?: 'On file' }}
                    </p>
                </div>
                <span class="record-status" style="background: rgba(100, 188, 180, 0.15); color: #164e63;">Accredited</span>
            </div>
            @endforeach
        </div>
    </section>
</div>

<!-- 3. Complete Invoices Ledger & Refund Authorizations -->
<section class="admin-panel" style="margin-top: 0;">
    <div class="admin-section-head">
        <div>
            <h2>Invoices & receipt register</h2>
            <p>Complete itemized transactions, cashier details, reconciliation stamps, and refund authorizations.</p>
        </div>
    </div>

    <div class="admin-record-list">
        @forelse($invoices as $inv)
        <div class="admin-record" style="display: flex; justify-content: space-between; align-items: flex-start; padding: 16px 18px;">
            <div style="flex: 1.5;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <strong style="font-size: 15px;">{{ $inv->invoice_number }}</strong>
                    <span style="font-size: 13px; color: #333;">— {{ $inv->patient->name }}</span>
                    <span class="record-status" style="font-size: 10px;
                        @if($inv->payment_status === 'refunded') background: rgba(224, 86, 76, 0.15); color: #C53030;
                        @else background: rgba(100, 188, 180, 0.15); color: #164e63;
                        @endif">
                        {{ ucfirst($inv->payment_status) }}
                    </span>
                    @if($inv->reconciled_at)
                        <span style="font-size: 10px; background: #EFE8E1; color: var(--moa-bronze); padding: 2px 6px; border-radius: 4px; font-weight: 600;">
                            <i class="bx bx-check"></i> Reconciled
                        </span>
                    @endif
                </div>

                <div style="font-size: 12px; color: var(--moa-slate); margin: 4px 0 8px;">
                    Issued {{ $inv->created_at->format('M d, Y · g:i A') }} by {{ $inv->creator ? $inv->creator->name : 'Clinic Staff' }} · Method: {{ ucfirst($inv->payment_method) }}
                </div>

                <div style="background: #FAF8F5; border-radius: 6px; padding: 8px 12px; font-size: 12px; display: inline-block;">
                    @foreach($inv->items as $item)
                        <div>• {{ $item->description }} (Qty: {{ $item->quantity }}) — ₱{{ number_format($item->line_total, 2) }}</div>
                    @endforeach
                    @if($inv->hmo_covered_amount > 0)
                        <div style="color: var(--moa-teal); font-weight: 600;">• HMO Allowance: -₱{{ number_format($inv->hmo_covered_amount, 2) }}</div>
                    @endif
                </div>

                @if($inv->refund_reason)
                    <div style="font-size: 12px; color: #C53030; margin-top: 6px;">
                        <em>Refund Note: {{ $inv->refund_reason }} (Authorized by: {{ $inv->refundApprover ? $inv->refundApprover->name : 'Administrator' }})</em>
                    </div>
                @endif
            </div>

            <!-- Total and Admin Refund Button -->
            <div style="text-align: right; min-width: 180px;">
                <div style="font: 700 22px/1 'Alegreya SC', serif; color: var(--moa-bronze);">
                    ₱{{ number_format($inv->patient_payable_amount, 2) }}
                </div>
                <div style="font-size: 11px; color: var(--moa-slate); margin-bottom: 8px;">Subtotal: ₱{{ number_format($inv->subtotal, 2) }}</div>

                @if($inv->payment_status === 'paid' && auth()->user()->isAdmin())
                    <details>
                        <summary style="font-size: 11px; color: #C53030; cursor: pointer; text-decoration: underline;">
                            Authorize Refund
                        </summary>
                        <form method="POST" action="{{ route('admin.billing.refund', $inv) }}" style="margin-top: 6px; text-align: left;" onsubmit="return confirm('Authorize refund for this invoice? Physical inventory frames will be automatically restocked.');">
                            @csrf
                            <input type="text" name="refund_reason" placeholder="Refund justification..." required style="width: 100%; padding: 4px; font-size: 11px; border: 1px solid #DDD; border-radius: 4px; box-sizing: border-box; margin-bottom: 4px;">
                            <button type="submit" class="button" style="background: #C53030; color: #fff; font-size: 10px; padding: 4px 8px; width: 100%;">
                                Confirm Refund
                            </button>
                        </form>
                    </details>
                @endif
            </div>
        </div>
        @empty
        <div class="admin-empty">No invoices recorded.</div>
        @endforelse
    </div>
    {{ $invoices->links() }}
</section>
@endsection

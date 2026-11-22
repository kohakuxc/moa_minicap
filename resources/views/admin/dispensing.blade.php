@extends('admin.layout')

@section('title', 'Optical Dispensing & POS')

@section('content')
<div class="admin-page-head">
    <div>
        <h1>Optical dispensing & <em>inventory POS.</em></h1>
        <p>Pull active patient prescriptions, match frame specifications, calculate lens packages, and route jobs to external labs.</p>
    </div>
</div>

@if(session('success'))
    <p class="portal-feedback" role="status" style="border-left: 4px solid var(--moa-teal); background: rgba(100, 188, 180, 0.12); color: #164e63;">
        <i class="bx bx-check-circle"></i> {{ session('success') }}
    </p>
@endif

@if($errors->any())
    <div class="admin-validation" role="alert">
        <strong>Check dispensing request:</strong>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 24px;">
    <!-- 1. Dispensing POS Counter -->
    <section class="admin-panel" style="margin-top: 0;">
        <div class="admin-section-head">
            <div>
                <h2>Optical POS & lens calculator</h2>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.dispensing.checkout') }}" data-admin-form style="display: flex; flex-direction: column; gap: 16px;">
            @csrf

            <!-- Patient Selector -->
            <label style="display: flex; flex-direction: column; gap: 5px; font-weight: 600; font-size: 13px;">
                 <span class="required">Select Patient</span>
                <select name="patient_id" required style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 14px;" onchange="window.location.href='{{ route('admin.dispensing') }}?patient_id=' + this.value">
                    <option value="">-- Choose Patient --</option>
                    @foreach($patients as $pat)
                        <option value="{{ $pat->id }}" @selected($selectedPatient && $selectedPatient->id == $pat->id)>
                            {{ $pat->name }} ({{ $pat->email }})
                        </option>
                    @endforeach
                </select>
            </label>

            <!-- Active Prescription Banner (if patient selected) -->
            @if($selectedPatient)
                <div style="background: rgba(240, 186, 127, 0.18); border: 1px solid var(--moa-gold); border-radius: 8px; padding: 12px 14px; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <strong style="color: var(--moa-bronze);">Active Prescriptions on File:</strong>
                        <span style="font-size: 11px; color: var(--moa-slate);">{{ $activePrescriptions->count() }} records found</span>
                    </div>
                    @forelse($activePrescriptions as $rx)
                        <div style="padding: 4px 0; border-top: 1px dashed rgba(159, 101, 44, 0.2); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <input type="radio" name="prescription_id" value="{{ $rx->id }}" id="rx_{{ $rx->id }}" @checked($loop->first)>
                                <label for="rx_{{ $rx->id }}" style="cursor: pointer; margin-left: 4px;">
                                    <strong>{{ $rx->eye }} ({{ ucfirst($rx->rx_type) }}):</strong>
                                    SPH {{ $rx->sphere ?? '0.00' }} · CYL {{ $rx->cylinder ?? '0.00' }} · AXIS {{ $rx->axis ?? '0' }} · PD {{ $rx->pd ?? '—' }} mm
                                </label>
                            </div>
                            <small style="color: var(--moa-slate);">Issued {{ $rx->issued_at->format('M d, Y') }}</small>
                        </div>
                    @empty
                        <div style="color: var(--moa-slate);">No prescription on file. General retail fitting will apply.</div>
                    @endforelse
                </div>
            @endif

            <!-- Frame Selector -->
            <label style="display: flex; flex-direction: column; gap: 5px; font-weight: 600; font-size: 13px;">
                 <span class="required">Eyewear Frame</span>
                <select name="frame_id" id="frame_select" required style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;" onchange="updateCartTotal()">
                    <option value="" data-price="0">-- Choose Frame from Stock --</option>
                    @foreach($frames as $fr)
                        <option value="{{ $fr->id }}" data-price="{{ $fr->retail_price }}" @disabled($fr->stock_quantity <= 0)>
                            {{ $fr->brand }} {{ $fr->model_name }} ({{ $fr->eye_size }}-{{ $fr->bridge_size }}-{{ $fr->temple_length }}) · {{ ucfirst($fr->color) }} — ₱{{ number_format($fr->retail_price, 2) }} [{{ $fr->stock_quantity }} left]
                        </option>
                    @endforeach
                </select>
            </label>

            <!-- Lens Catalog Selector -->
            <label style="display: flex; flex-direction: column; gap: 5px; font-weight: 600; font-size: 13px;">
                 <span class="required">Ophthalmic Lens Package</span>
                <select name="lens_catalog_id" id="lens_select" required style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;" onchange="updateCartTotal()">
                    <option value="" data-price="0">-- Choose Lens Package --</option>
                    @foreach($lenses as $ln)
                        <option value="{{ $ln->id }}" data-price="{{ $ln->price }}">
                            {{ $ln->name }} (Index {{ $ln->index }}) · {{ $ln->coating }} — ₱{{ number_format($ln->price, 2) }}
                        </option>
                    @endforeach
                </select>
            </label>

            <!-- HMO Coverage & Insurance Section -->
            <div style="background: #FAFAF8; border: 1px solid #EBE4DC; border-radius: 8px; padding: 14px;">
                <div style="font-weight: 700; font-size: 12px; color: var(--moa-bronze); margin-bottom: 10px;">HMO / OPTICAL INSURANCE COVERAGE</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                        Accredited Provider
                        <select name="hmo_provider_id" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
                            <option value="">-- Direct Private Pay --</option>
                            @foreach($hmoProviders as $hmo)
                                <option value="{{ $hmo->id }}">{{ $hmo->name }} ({{ $hmo->code }})</option>
                            @endforeach
                        </select>
                    </label>
                    <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                        HMO Card / Member ID
                        <input type="text" name="hmo_member_id" placeholder="e.g. MAX-104928" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;">
                    </label>
                </div>
                <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600; margin-top: 10px;">
                    HMO Covered Allowance (PHP)
                    <input type="number" step="0.01" name="hmo_covered_amount" id="hmo_amount" value="0.00" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px;" oninput="updateCartTotal()">
                </label>
            </div>

            <!-- Optical Lab Pipeline Option -->
            <div style="background: rgba(100, 188, 180, 0.08); border: 1px dashed var(--moa-teal); border-radius: 8px; padding: 14px;">
                <label style="display: flex; align-items: center; gap: 8px; font-weight: 700; color: #164e63; font-size: 13px; cursor: pointer;">
                    <input type="checkbox" name="send_to_lab" value="1" checked id="send_to_lab_check">
                    Route Job to Optical Lab Pipeline (Edging & Surfacing)
                </label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                    <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                        Optical Lab Facility
                        <input type="text" name="lab_vendor" value="In-House Optical Edging Lab" style="padding: 7px; border: 1px solid #D5CBC0; border-radius: 6px;">
                    </label>
                    <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                        Fabrication Notes
                        <input type="text" name="lab_notes" placeholder="e.g. Drill-mount rimless, bevel groove" style="padding: 7px; border: 1px solid #D5CBC0; border-radius: 6px;">
                    </label>
                </div>
            </div>

            <!-- Payment Method & Summary -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 2px solid #EAE4DC; padding-top: 14px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600;">
                    Payment Method:
                    <select name="payment_method" style="padding: 6px 10px; border: 1px solid #D5CBC0; border-radius: 6px;">
                        <option value="cash">Cash Counter</option>
                        <option value="card">Credit / Debit Card</option>
                        <option value="hmo">HMO Direct Settlement</option>
                        <option value="online">Online / GCash</option>
                        <option value="split">Split Payment</option>
                    </select>
                </label>
                <div style="text-align: right;">
                    <div style="font-size: 12px; color: var(--moa-slate);">Total Due After HMO:</div>
                    <div style="font: 700 24px/1 'Alegreya SC', serif; color: var(--moa-bronze);" id="total_display">₱0.00</div>
                </div>
            </div>

            <button class="button button-teal button-primary" type="submit" data-admin-submit style="padding: 12px; font-size: 14px;">
                Complete Dispensing & Generate Invoice
            </button>
        </form>
    </section>

    <!-- 2. Eyewear Inventory Manager (Frames & Stock) -->
    <section class="admin-panel" style="margin-top: 0;">
        <div class="admin-section-head">
            <div>
                <div class="eyebrow" style="color: var(--moa-bronze);"></div>
                <h2>Optical frames inventory</h2>
                <p>Register new optical frame SKUs and adjust stock levels.</p>
            </div>
            <details style="position: relative;">
                <summary class="button button-quiet" style="font-size: 12px; cursor: pointer;"><i class="bx bx-plus"></i> New Frame SKU</summary>
                <div style="position: absolute; right: 0; top: 100%; width: 360px; background: #fff; border: 1px solid #E5DFD7; border-radius: 8px; padding: 18px; box-shadow: 0 8px 24px rgba(0,0,0,0.12); z-index: 20; margin-top: 6px;">
                    <form method="POST" action="{{ route('admin.dispensing.frames.store') }}" style="display: flex; flex-direction: column; gap: 8px;">
                        @csrf
                        <div style="font-weight: 700; font-size: 13px; margin-bottom: 4px;">Register Eyewear Frame</div>
                        <input type="text" name="sku" placeholder="SKU (e.g. MOA-AT-009)" required style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                            <input type="text" name="brand" placeholder="Brand" required style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                            <input type="text" name="model_name" placeholder="Model Name" required style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                            <select name="category" style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                                <option value="spectacle">Spectacle</option>
                                <option value="sunglasses">Sunglasses</option>
                                <option value="reading">Reading</option>
                            </select>
                            <select name="shape" style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                                <option value="rectangular">Rectangular</option>
                                <option value="round">Round</option>
                                <option value="cat-eye">Cat-Eye</option>
                                <option value="aviator">Aviator</option>
                                <option value="geometric">Geometric</option>
                            </select>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                            <input type="text" name="material" placeholder="Material (e.g. titanium)" value="acetate" required style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                            <input type="text" name="color" placeholder="Color" value="Black" required style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px;">
                            <input type="number" name="eye_size" placeholder="Eye mm" value="52" required style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                            <input type="number" name="bridge_size" placeholder="Bridge mm" value="18" required style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                            <input type="number" name="temple_length" placeholder="Temple mm" value="140" required style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                            <input type="number" step="0.01" name="retail_price" placeholder="Retail (PHP)" required style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                            <input type="number" step="0.01" name="cost_price" placeholder="Cost (PHP)" style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                            <input type="number" name="stock_quantity" placeholder="Initial Stock" value="10" required style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                            <input type="number" name="reorder_threshold" placeholder="Safe Threshold" value="4" required style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                        </div>
                        <input type="text" name="supplier_name" placeholder="Supplier / Artisan Vendor" style="padding: 6px; border: 1px solid #DDD; border-radius: 4px; font-size: 12px;">
                        <input type="hidden" name="gender" value="unisex">
                        <button type="submit" class="button button-teal" style="font-size: 12px; margin-top: 4px;">Save Frame SKU</button>
                    </form>
                </div>
            </details>
        </div>

        <div class="admin-record-list" style="max-height: 480px; overflow-y: auto;">
            @foreach($frames as $fr)
            <div class="admin-record" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px;">
                <div>
                    <div style="font-weight: 700; font-size: 14px;">{{ $fr->brand }} — {{ $fr->model_name }}</div>
                    <small style="color: var(--moa-slate);">
                        SKU: {{ $fr->sku }} · {{ $fr->eye_size }}-{{ $fr->bridge_size }}-{{ $fr->temple_length }} · {{ ucfirst($fr->material) }} · {{ ucfirst($fr->color) }}
                    </small>
                    <div style="font-weight: 600; color: var(--moa-bronze); font-size: 13px; margin-top: 2px;">
                        ₱{{ number_format($fr->retail_price, 2) }}
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="record-status" style="{{ $fr->isLowStock() ? 'background: rgba(224, 86, 76, 0.15); color: #C53030; font-weight: 700;' : '' }}">
                        {{ $fr->stock_quantity }} in stock
                    </span>
                    <form method="POST" action="{{ route('admin.dispensing.frames.adjust', $fr) }}" style="display: flex; gap: 4px; margin: 0;">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="adjustment" value="5">
                        <button type="submit" class="button button-quiet" title="Restock +5 units" style="font-size: 11px; padding: 4px 8px;">
                            +5
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </section>
</div>

<!-- 3. Recent Dispensed Invoices -->
<section class="admin-panel" style="margin-top: 24px;">
    <div class="admin-section-head">
        <div>
            <h2>Recent optical sales & dispensing receipts</h2>
            <p>Itemized invoices with frame, lens breakdown, and lab job linkages.</p>
        </div>
        <a href="{{ route('admin.billing') }}" class="button button-quiet">View Financial Ledger <i class="bx bx-right-arrow-alt"></i></a>
    </div>

    <div class="admin-record-list">
        @forelse($recentInvoices as $inv)
        <div class="admin-record" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <strong style="font-size: 14px;">{{ $inv->patient->name }}</strong>
                    <span class="staff-role-badge">{{ $inv->invoice_number }}</span>
                    @if($inv->labOrder)
                        <span style="font-size: 11px; background: rgba(100, 188, 180, 0.2); color: #164e63; padding: 2px 6px; border-radius: 4px; font-weight: 600;">
                            Lab: {{ $inv->labOrder->order_number }}
                        </span>
                    @endif
                </div>
                <small style="color: var(--moa-slate);">
                    {{ $inv->created_at->format('M d, Y · g:i A') }} · Method: {{ ucfirst($inv->payment_method) }}
                    @if($inv->hmo_covered_amount > 0) · HMO: ₱{{ number_format($inv->hmo_covered_amount, 2) }} @endif
                </small>
            </div>
            <div style="text-align: right;">
                <div style="font: 700 16px/1 'Alegreya SC', serif; color: var(--moa-bronze);">
                    ₱{{ number_format($inv->patient_payable_amount, 2) }}
                </div>
                <span class="record-status" style="background: rgba(100, 188, 180, 0.15); color: #164e63; font-size: 10px;">
                    {{ ucfirst($inv->payment_status) }}
                </span>
            </div>
        </div>
        @empty
        <div class="admin-empty">No invoices created yet.</div>
        @endforelse
    </div>
    {{ $recentInvoices->links() }}
</section>

<script>
function updateCartTotal() {
    const frameSelect = document.getElementById('frame_select');
    const lensSelect = document.getElementById('lens_select');
    const hmoInput = document.getElementById('hmo_amount');
    const totalDisplay = document.getElementById('total_display');

    const framePrice = parseFloat(frameSelect.options[frameSelect.selectedIndex]?.dataset.price || 0);
    const lensPrice = parseFloat(lensSelect.options[lensSelect.selectedIndex]?.dataset.price || 0);
    const hmoCoverage = parseFloat(hmoInput.value || 0);

    const subtotal = framePrice + lensPrice;
    const patientDue = Math.max(0, subtotal - hmoCoverage);

    totalDisplay.textContent = '₱' + patientDue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
@endsection

@extends('admin.layout')

@section('title', 'Optical Lab Pipeline')

@section('content')
<div class="admin-page-head">
    <div>
        <div class="eyebrow" style="color: var(--moa-bronze);"></div>
        <h1>Optical lab <em>fabrication pipeline.</em></h1>
        <p>Monitor prescription lens surfacing, beveling, coating, quality checks, and dispensing handoffs.</p>
    </div>
</div>

@if(session('success'))
    <p class="portal-feedback" role="status" style="border-left: 4px solid var(--moa-teal); background: rgba(100, 188, 180, 0.12); color: #164e63;">
        <i class="bx bx-check-circle"></i> {{ session('success') }}
    </p>
@endif

<!-- Pipeline Stage Filter Pills -->
<div style="display: flex; gap: 10px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 4px;">
    <a href="{{ route('admin.lab-orders') }}" class="button {{ empty($selectedStatus) ? 'button-dark' : 'button-quiet' }}" style="font-size: 12px; padding: 6px 14px;">
        All Stages ({{ array_sum($pipelineCounts) }})
    </a>
    <a href="{{ route('admin.lab-orders', ['status' => 'ordered']) }}" class="button {{ $selectedStatus === 'ordered' ? 'button-dark' : 'button-quiet' }}" style="font-size: 12px; padding: 6px 14px;">
        1. Ordered ({{ $pipelineCounts['ordered'] }})
    </a>
    <a href="{{ route('admin.lab-orders', ['status' => 'in_edging_coating']) }}" class="button {{ $selectedStatus === 'in_edging_coating' ? 'button-dark' : 'button-quiet' }}" style="font-size: 12px; padding: 6px 14px;">
        2. In Edging & Coating ({{ $pipelineCounts['in_edging_coating'] }})
    </a>
    <a href="{{ route('admin.lab-orders', ['status' => 'quality_check']) }}" class="button {{ $selectedStatus === 'quality_check' ? 'button-dark' : 'button-quiet' }}" style="font-size: 12px; padding: 6px 14px;">
        3. Quality Check ({{ $pipelineCounts['quality_check'] }})
    </a>
    <a href="{{ route('admin.lab-orders', ['status' => 'ready_for_pickup']) }}" class="button {{ $selectedStatus === 'ready_for_pickup' ? 'button-dark' : 'button-quiet' }}" style="font-size: 12px; padding: 6px 14px;">
        4. Ready for Pickup ({{ $pipelineCounts['ready_for_pickup'] }})
    </a>
    <a href="{{ route('admin.lab-orders', ['status' => 'dispensed']) }}" class="button {{ $selectedStatus === 'dispensed' ? 'button-dark' : 'button-quiet' }}" style="font-size: 12px; padding: 6px 14px;">
        5. Dispensed ({{ $pipelineCounts['dispensed'] }})
    </a>
</div>

<!-- Orders Table -->
<section class="admin-panel" style="margin-top: 0;">
    <div class="admin-section-head">
        <div>
            <h2>Active laboratory orders</h2>
            <p>Track progress with external edging labs or internal fabrication workshops.</p>
        </div>
    </div>

    <div class="admin-record-list">
        @forelse($orders as $order)
        <div class="admin-record" style="display: flex; justify-content: space-between; align-items: flex-start; padding: 18px 20px;">
            <div style="flex: 1.2;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <span class="ticket-badge" style="font-size: 13px;">{{ $order->order_number }}</span>
                    <strong style="font-size: 15px;">{{ $order->patient->name }}</strong>
                    <span style="font-size: 12px; color: var(--moa-slate);">
                        Ordered {{ $order->created_at->format('M d, Y') }} by {{ $order->optician ? $order->optician->name : 'Optician Team' }}
                    </span>
                </div>

                <div style="font-size: 13px; color: #4A4540; margin: 6px 0;">
                    <strong>Frame:</strong> {{ $order->frame ? $order->frame->brand.' '.$order->frame->model_name.' ('.$order->frame->eye_size.'-'.$order->frame->bridge_size.'-'.$order->frame->temple_length.')' : 'Patient Supplied Frame' }}
                    <br>
                    <strong>Lenses:</strong> {{ $order->lensCatalog ? $order->lensCatalog->name.' ('.$order->lensCatalog->coating.')' : 'Custom Rx' }}
                    <br>
                    <strong>Lab Facility:</strong> <span style="color: var(--moa-bronze); font-weight: 600;">{{ $order->lab_vendor }}</span>
                    @if($order->estimated_completion_date)
                        · <small style="color: var(--moa-slate);">ETA: {{ $order->estimated_completion_date->format('M d, Y') }}</small>
                    @endif
                </div>

                @if($order->prescription)
                <div style="background: #F8F5F0; border-radius: 6px; padding: 6px 10px; font-size: 11px; font-family: monospace; color: #555; display: inline-block; margin-top: 4px;">
                    Rx: SPH {{ $order->prescription->sphere ?? '0.00' }} · CYL {{ $order->prescription->cylinder ?? '0.00' }} · AXIS {{ $order->prescription->axis ?? '0' }} · PD {{ $order->prescription->pd ?? '—' }}
                </div>
                @endif

                @if($order->lab_notes)
                    <div style="font-size: 12px; color: #666; margin-top: 6px;"><em>Notes: {{ $order->lab_notes }}</em></div>
                @endif
            </div>

            <!-- Transition Form -->
            <div style="text-align: right; min-width: 280px;">
                <div style="margin-bottom: 8px;">
                    <span class="record-status" style="font-size: 12px; font-weight: 700;
                        @if($order->status === 'ordered') background: rgba(240, 186, 127, 0.25); color: #7A5530;
                        @elseif($order->status === 'in_edging_coating') background: rgba(159, 101, 44, 0.15); color: #9F652C;
                        @elseif($order->status === 'quality_check') background: rgba(240, 186, 127, 0.35); color: #553C00;
                        @elseif($order->status === 'ready_for_pickup') background: rgba(100, 188, 180, 0.25); color: #164e63;
                        @elseif($order->status === 'dispensed') background: rgba(100, 188, 180, 0.4); color: #0d3b4c;
                        @endif">
                        ● {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                    </span>
                </div>

                <form method="POST" action="{{ route('admin.lab-orders.status', $order) }}" style="display: flex; gap: 6px; justify-content: flex-end; align-items: center; margin: 0;">
                    @csrf
                    @method('PATCH')
                    <select name="status" style="padding: 6px 8px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 12px;">
                        <option value="ordered" @selected($order->status === 'ordered')>1. Ordered</option>
                        <option value="in_edging_coating" @selected($order->status === 'in_edging_coating')>2. In Edging & Coating</option>
                        <option value="quality_check" @selected($order->status === 'quality_check')>3. Quality Check</option>
                        <option value="ready_for_pickup" @selected($order->status === 'ready_for_pickup')>4. Ready for Pickup</option>
                        <option value="dispensed" @selected($order->status === 'dispensed')>5. Dispensed to Patient</option>
                        <option value="cancelled" @selected($order->status === 'cancelled')>Cancelled</option>
                    </select>
                    <button type="submit" class="button button-quiet" style="font-size: 11px; padding: 6px 10px;">
                        Update
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="admin-empty">No lab orders in this stage.</div>
        @endforelse
    </div>
    {{ $orders->links() }}
</section>
@endsection

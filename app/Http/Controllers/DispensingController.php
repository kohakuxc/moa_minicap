<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Frame;
use App\Models\HmoClaim;
use App\Models\HmoProvider;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LabOrder;
use App\Models\LensCatalog;
use App\Models\Prescription;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class DispensingController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Invoice::class);

        $selectedPatientId = $request->query('patient_id');
        $selectedPatient = $selectedPatientId ? User::with('prescriptions')->find($selectedPatientId) : null;

        $patients = User::where('role', User::ROLE_PATIENT)->orderBy('name')->get(['id', 'name', 'email', 'phone']);
        $frames = Frame::where('is_active', true)->orderBy('brand')->orderBy('model_name')->get();
        $lenses = LensCatalog::where('is_active', true)->orderBy('price')->get();
        $hmoProviders = HmoProvider::where('is_active', true)->orderBy('name')->get();

        $activePrescriptions = $selectedPatient
            ? $selectedPatient->prescriptions()->latest('issued_at')->take(4)->get()
            : collect();

        $recentInvoices = Invoice::with(['patient', 'items', 'labOrder'])
            ->latest('created_at')
            ->paginate(10);

        return view('admin.dispensing', [
            'selectedPatient' => $selectedPatient,
            'patients' => $patients,
            'frames' => $frames,
            'lenses' => $lenses,
            'hmoProviders' => $hmoProviders,
            'activePrescriptions' => $activePrescriptions,
            'recentInvoices' => $recentInvoices,
        ]);
    }

    public function storeFrame(Request $request)
    {
        Gate::authorize('manage', Frame::class);

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:60', 'unique:frames,sku'],
            'brand' => ['required', 'string', 'max:100'],
            'model_name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'in:spectacle,sunglasses,reading,kids'],
            'shape' => ['required', 'string', 'max:50'],
            'material' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'max:50'],
            'gender' => ['required', 'string', 'in:unisex,mens,womens,kids'],
            'eye_size' => ['required', 'integer', 'min:30', 'max:75'],
            'bridge_size' => ['required', 'integer', 'min:10', 'max:35'],
            'temple_length' => ['required', 'integer', 'min:100', 'max:170'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'reorder_threshold' => ['required', 'integer', 'min:1'],
            'supplier_name' => ['nullable', 'string', 'max:120'],
            'is_consignment' => ['nullable', 'boolean'],
        ]);

        $validated['is_consignment'] = (bool) ($validated['is_consignment'] ?? false);
        $frame = Frame::create($validated);

        AuditLog::record($request->user(), 'frame.created', $frame);

        return back()->with('success', "Frame '{$frame->brand} {$frame->model_name}' (SKU: {$frame->sku}) added to inventory.");
    }

    public function adjustStock(Request $request, Frame $frame)
    {
        Gate::authorize('manage', Frame::class);

        $validated = $request->validate([
            'adjustment' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $newQty = max(0, $frame->stock_quantity + $validated['adjustment']);
        $frame->update(['stock_quantity' => $newQty]);

        AuditLog::record($request->user(), 'frame.stock_adjusted:' . $validated['adjustment'], $frame);

        return back()->with('success', "Stock updated for {$frame->model_name}: {$newQty} units currently on hand.");
    }

    public function checkout(Request $request)
    {
        Gate::authorize('create', Invoice::class);

        $validated = $request->validate([
            'patient_id' => ['required', 'exists:users,id'],
            'prescription_id' => ['nullable', 'exists:prescriptions,id'],
            'frame_id' => ['required', 'exists:frames,id'],
            'lens_catalog_id' => ['required', 'exists:lens_catalogs,id'],
            'hmo_provider_id' => ['nullable', 'exists:hmo_providers,id'],
            'hmo_member_id' => ['nullable', 'string', 'max:100'],
            'hmo_covered_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'in:cash,card,hmo,online,split'],
            'send_to_lab' => ['nullable', 'boolean'],
            'lab_vendor' => ['nullable', 'string', 'max:120'],
            'lab_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $patient = User::findOrFail($validated['patient_id']);
        $frame = Frame::findOrFail($validated['frame_id']);
        $lens = LensCatalog::findOrFail($validated['lens_catalog_id']);

        abort_if($frame->stock_quantity <= 0, 422, 'Selected frame is currently out of stock.');

        $subtotal = $frame->retail_price + $lens->price;
        $hmoCovered = min($subtotal, (float) ($validated['hmo_covered_amount'] ?? 0.00));
        $patientPayable = max(0.00, $subtotal - $hmoCovered);

        $invoice = DB::transaction(function () use ($request, $validated, $patient, $frame, $lens, $subtotal, $hmoCovered, $patientPayable) {
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            // Create Invoice
            $inv = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'patient_id' => $patient->id,
                'hmo_provider_id' => $validated['hmo_provider_id'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => 0.00,
                'hmo_covered_amount' => $hmoCovered,
                'patient_payable_amount' => $patientPayable,
                'total_paid' => $patientPayable,
                'payment_method' => $validated['payment_method'],
                'payment_status' => Invoice::STATUS_PAID,
                'created_by' => $request->user()->id,
            ]);

            // Add Frame Item
            InvoiceItem::create([
                'invoice_id' => $inv->id,
                'item_type' => 'frame',
                'item_id' => $frame->id,
                'description' => "Frame: {$frame->brand} {$frame->model_name} ({$frame->sku})",
                'quantity' => 1,
                'unit_price' => $frame->retail_price,
                'line_total' => $frame->retail_price,
            ]);

            // Add Lens Item
            InvoiceItem::create([
                'invoice_id' => $inv->id,
                'item_type' => 'lens',
                'item_id' => $lens->id,
                'description' => "Lens: {$lens->name} ({$lens->coating})",
                'quantity' => 1,
                'unit_price' => $lens->price,
                'line_total' => $lens->price,
            ]);

            // Decrement Frame Stock
            $frame->decrement('stock_quantity');

            // HMO Claim if applicable
            if ($hmoCovered > 0 && ! empty($validated['hmo_provider_id'])) {
                $claimNumber = 'HMO-' . date('Ymd') . '-' . strtoupper(Str::random(4));
                HmoClaim::create([
                    'claim_number' => $claimNumber,
                    'invoice_id' => $inv->id,
                    'hmo_provider_id' => $validated['hmo_provider_id'],
                    'patient_id' => $patient->id,
                    'member_id' => $validated['hmo_member_id'] ?? 'HMO-MEM-' . $patient->id,
                    'claimed_amount' => $hmoCovered,
                    'approved_amount' => 0.00,
                    'status' => HmoClaim::STATUS_PENDING,
                ]);
            }

            // Route to Optical Lab Order if requested
            if (! empty($validated['send_to_lab'])) {
                $labOrderNumber = 'LAB-' . date('Ymd') . '-' . strtoupper(Str::random(4));
                $labOrder = LabOrder::create([
                    'order_number' => $labOrderNumber,
                    'patient_id' => $patient->id,
                    'prescription_id' => $validated['prescription_id'] ?? null,
                    'frame_id' => $frame->id,
                    'lens_catalog_id' => $lens->id,
                    'optician_id' => $request->user()->id,
                    'lab_vendor' => $validated['lab_vendor'] ?: 'In-House Edging',
                    'status' => LabOrder::STATUS_ORDERED,
                    'estimated_completion_date' => now()->addDays(3)->toDateString(),
                    'lab_notes' => $validated['lab_notes'] ?? null,
                ]);

                $inv->update(['lab_order_id' => $labOrder->id]);
                AuditLog::record($request->user(), 'lab_order.routed_from_pos', $labOrder);
            }

            // Conclude Queue Ticket if patient is currently at Dispensing
            $queueTicket = QueueTicket::where('patient_id', $patient->id)
                ->where('status', QueueTicket::STATUS_DISPENSING)
                ->latest('check_in_time')
                ->first();

            if ($queueTicket) {
                $queueTicket->update([
                    'status' => QueueTicket::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);
                AuditLog::record($request->user(), 'queue_ticket.completed_at_dispensing', $queueTicket);
            }

            AuditLog::record($request->user(), 'invoice.dispensed_and_paid', $inv);

            return $inv;
        });

        return redirect()->route('admin.dispensing')
            ->with('success', "Dispensing complete! Invoice #{$invoice->invoice_number} generated for {$patient->name}.");
    }
}

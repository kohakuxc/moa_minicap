<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LabOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LabOrderController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', LabOrder::class);

        $statusFilter = $request->query('status');

        $query = LabOrder::with(['patient', 'prescription', 'frame', 'lensCatalog', 'optician'])
            ->latest('created_at');

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $orders = $query->paginate(15);

        // Group counts for kanban pipeline headers
        $pipelineCounts = [
            'ordered' => LabOrder::where('status', LabOrder::STATUS_ORDERED)->count(),
            'in_edging_coating' => LabOrder::where('status', LabOrder::STATUS_IN_EDGING_COATING)->count(),
            'quality_check' => LabOrder::where('status', LabOrder::STATUS_QUALITY_CHECK)->count(),
            'ready_for_pickup' => LabOrder::where('status', LabOrder::STATUS_READY_FOR_PICKUP)->count(),
            'dispensed' => LabOrder::where('status', LabOrder::STATUS_DISPENSED)->count(),
        ];

        return view('admin.lab-orders', [
            'orders' => $orders,
            'pipelineCounts' => $pipelineCounts,
            'selectedStatus' => $statusFilter,
        ]);
    }

    public function updateStatus(Request $request, LabOrder $labOrder)
    {
        Gate::authorize('update', $labOrder);

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                LabOrder::STATUS_ORDERED,
                LabOrder::STATUS_IN_EDGING_COATING,
                LabOrder::STATUS_QUALITY_CHECK,
                LabOrder::STATUS_READY_FOR_PICKUP,
                LabOrder::STATUS_DISPENSED,
                LabOrder::STATUS_CANCELLED,
            ])],
            'lab_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $updates = ['status' => $validated['status']];
        if (! empty($validated['lab_notes'])) {
            $updates['lab_notes'] = $validated['lab_notes'];
        }

        if ($validated['status'] === LabOrder::STATUS_DISPENSED) {
            $updates['completed_at'] = now();
        }

        $labOrder->update($updates);
        AuditLog::record($request->user(), 'lab_order.status_updated:' . $validated['status'], $labOrder);

        return back()->with('success', "Lab order {$labOrder->order_number} moved to " . ucfirst(str_replace('_', ' ', $validated['status'])) . ".");
    }
}

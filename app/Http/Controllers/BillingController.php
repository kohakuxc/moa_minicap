<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Frame;
use App\Models\HmoClaim;
use App\Models\HmoProvider;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Invoice::class);

        $today = now()->toDateString();

        $invoices = Invoice::with(['patient', 'hmoProvider', 'items', 'creator', 'refundApprover'])
            ->latest('created_at')
            ->paginate(12, ['*'], 'invoices');

        $hmoProviders = HmoProvider::withCount('claims')->get();

        $hmoClaims = HmoClaim::with(['invoice', 'hmoProvider', 'patient'])
            ->latest('created_at')
            ->paginate(10, ['*'], 'claims');

        // Financial totals
        $todayPaidInvoices = Invoice::whereDate('created_at', $today)->where('payment_status', Invoice::STATUS_PAID);
        $todayTotal = (float) (clone $todayPaidInvoices)->sum('total_paid');
        $todayCash = (float) (clone $todayPaidInvoices)->where('payment_method', 'cash')->sum('total_paid');
        $todayCard = (float) (clone $todayPaidInvoices)->where('payment_method', 'card')->sum('total_paid');
        $todayHmoCovered = (float) Invoice::whereDate('created_at', $today)->sum('hmo_covered_amount');

        $pendingClaimsCount = HmoClaim::where('status', HmoClaim::STATUS_PENDING)->count();
        $unreconciledCount = Invoice::whereNull('reconciled_at')->where('payment_status', Invoice::STATUS_PAID)->count();

        return view('admin.billing', [
            'invoices' => $invoices,
            'hmoProviders' => $hmoProviders,
            'hmoClaims' => $hmoClaims,
            'todayStats' => [
                'total' => $todayTotal,
                'cash' => $todayCash,
                'card' => $todayCard,
                'hmo' => $todayHmoCovered,
                'unreconciled_count' => $unreconciledCount,
                'pending_claims_count' => $pendingClaimsCount,
            ],
        ]);
    }

    public function storeHmoProvider(Request $request)
    {
        Gate::authorize('create', Invoice::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:50', 'unique:hmo_providers,code'],
            'contact_email' => ['nullable', 'email', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'coverage_terms' => ['nullable', 'string', 'max:1000'],
        ]);

        $provider = HmoProvider::create($validated);
        AuditLog::record($request->user(), 'hmo_provider.registered', $provider);

        return back()->with('success', "HMO Provider '{$provider->name}' successfully accredited.");
    }

    public function updateClaimStatus(Request $request, HmoClaim $claim)
    {
        Gate::authorize('create', Invoice::class);

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                HmoClaim::STATUS_PENDING,
                HmoClaim::STATUS_SUBMITTED,
                HmoClaim::STATUS_APPROVED,
                HmoClaim::STATUS_REJECTED,
            ])],
            'approval_code' => ['nullable', 'string', 'max:100'],
            'approved_amount' => ['nullable', 'numeric', 'min:0'],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $updates = ['status' => $validated['status']];

        if ($validated['status'] === HmoClaim::STATUS_SUBMITTED) {
            $updates['submitted_at'] = now();
        }

        if ($validated['status'] === HmoClaim::STATUS_APPROVED) {
            $updates['approval_code'] = $validated['approval_code'] ?? 'APPR-' . strtoupper(bin2hex(random_bytes(3)));
            $updates['approved_amount'] = $validated['approved_amount'] ?? $claim->claimed_amount;
            $updates['settled_at'] = now();
        }

        if ($validated['status'] === HmoClaim::STATUS_REJECTED) {
            $updates['rejection_reason'] = $validated['rejection_reason'] ?? 'Not approved by provider';
        }

        $claim->update($updates);
        AuditLog::record($request->user(), 'hmo_claim.status_updated:' . $validated['status'], $claim);

        return back()->with('success', "HMO Claim {$claim->claim_number} updated to " . ucfirst($validated['status']) . ".");
    }

    public function reconcileDaily(Request $request)
    {
        Gate::authorize('create', Invoice::class);

        $count = Invoice::whereNull('reconciled_at')
            ->where('payment_status', Invoice::STATUS_PAID)
            ->update(['reconciled_at' => now()]);

        AuditLog::record($request->user(), 'billing.daily_reconciliation:' . $count, new Invoice());

        return back()->with('success', "End-of-day reconciliation complete. {$count} paid transactions balanced and closed.");
    }

    public function approveRefund(Request $request, Invoice $invoice)
    {
        Gate::authorize('approveRefund', Invoice::class);

        $validated = $request->validate([
            'refund_reason' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $invoice, $validated) {
            $invoice->update([
                'payment_status' => Invoice::STATUS_REFUNDED,
                'refund_reason' => $validated['refund_reason'],
                'refund_approved_by' => $request->user()->id,
            ]);

            // Restock any physical frame items from this invoice
            foreach ($invoice->items as $item) {
                if ($item->item_type === 'frame' && $item->item_id) {
                    Frame::where('id', $item->item_id)->increment('stock_quantity', $item->quantity);
                }
            }

            AuditLog::record($request->user(), 'invoice.refund_approved:' . $invoice->invoice_number, $invoice);
        });

        return back()->with('success', "Refund of ₱" . number_format($invoice->patient_payable_amount, 2) . " approved for invoice {$invoice->invoice_number}.");
    }
}

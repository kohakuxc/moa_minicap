<?php

namespace Tests\Unit;

use App\Models\Frame;
use App\Models\HmoClaim;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LabOrder;
use App\Models\LensCatalog;
use App\Models\TryOnReservation;
use Tests\TestCase;

class Phase3DispensingAndPortalTest extends TestCase
{
    public function test_dispensing_subtotal_and_hmo_calculation(): void
    {
        $frame = new Frame([
            'brand' => 'Aether Titanium',
            'retail_price' => 4850.00,
        ]);

        $lens = new LensCatalog([
            'name' => '1.60 Blue-Shield',
            'price' => 2400.00,
        ]);

        $subtotal = (float) $frame->retail_price + (float) $lens->price;
        $this->assertEquals(7250.00, $subtotal);

        // HMO coverage applied
        $hmoAllowance = 2500.00;
        $patientDue = max(0.00, $subtotal - $hmoAllowance);
        $this->assertEquals(4750.00, $patientDue);

        // Generous HMO allowance covering full amount
        $fullHmoAllowance = 8000.00;
        $patientDueFull = max(0.00, $subtotal - $fullHmoAllowance);
        $this->assertEquals(0.00, $patientDueFull);
    }

    public function test_lab_order_statuses_and_flow(): void
    {
        $this->assertSame('ordered', LabOrder::STATUS_ORDERED);
        $this->assertSame('in_edging_coating', LabOrder::STATUS_IN_EDGING_COATING);
        $this->assertSame('quality_check', LabOrder::STATUS_QUALITY_CHECK);
        $this->assertSame('ready_for_pickup', LabOrder::STATUS_READY_FOR_PICKUP);
        $this->assertSame('dispensed', LabOrder::STATUS_DISPENSED);
        $this->assertSame('cancelled', LabOrder::STATUS_CANCELLED);
    }

    public function test_hmo_claim_statuses(): void
    {
        $this->assertSame('pending', HmoClaim::STATUS_PENDING);
        $this->assertSame('submitted', HmoClaim::STATUS_SUBMITTED);
        $this->assertSame('approved', HmoClaim::STATUS_APPROVED);
        $this->assertSame('rejected', HmoClaim::STATUS_REJECTED);
    }

    public function test_invoice_statuses(): void
    {
        $this->assertSame('unpaid', Invoice::STATUS_UNPAID);
        $this->assertSame('partially_paid', Invoice::STATUS_PARTIALLY_PAID);
        $this->assertSame('paid', Invoice::STATUS_PAID);
        $this->assertSame('refunded', Invoice::STATUS_REFUNDED);
    }

    public function test_try_on_reservation_model(): void
    {
        $res = new TryOnReservation([
            'patient_id' => 5,
            'frame_id' => 1,
            'status' => 'reserved',
            'reserved_until' => now()->addHours(48),
        ]);

        $this->assertSame('reserved', $res->status);
        $this->assertTrue($res->reserved_until->isFuture());
    }
}

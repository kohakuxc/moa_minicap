<?php

namespace Tests\Unit;

use App\Models\ClinicService;
use App\Models\Frame;
use App\Models\QueueTicket;
use Tests\TestCase;

class Phase2ClinicalAndQueueTest extends TestCase
{
    public function test_digital_signature_hash_generation(): void
    {
        $patientId = 10;
        $optometristId = 2;
        $sphOd = -2.25;
        $cylOd = -0.75;
        $sphOs = -2.50;
        $cylOs = -0.50;
        $timestamp = 1732300000;
        $key = 'test-secret-key';

        $payload = implode('|', [
            $patientId,
            $optometristId,
            $sphOd,
            $cylOd,
            $sphOs,
            $cylOs,
            $timestamp,
            $key,
        ]);
        $hash1 = hash('sha256', $payload);
        $hash2 = hash('sha256', $payload);

        $this->assertSame(64, strlen($hash1));
        $this->assertSame($hash1, $hash2);

        // Tamper with refraction
        $tamperedPayload = implode('|', [
            $patientId,
            $optometristId,
            -3.00, // modified
            $cylOd,
            $sphOs,
            $cylOs,
            $timestamp,
            $key,
        ]);
        $tamperedHash = hash('sha256', $tamperedPayload);
        $this->assertNotSame($hash1, $tamperedHash);
    }

    public function test_queue_ticket_statuses(): void
    {
        $this->assertSame('waiting', QueueTicket::STATUS_WAITING);
        $this->assertSame('in_consultation', QueueTicket::STATUS_IN_CONSULTATION);
        $this->assertSame('dispensing', QueueTicket::STATUS_DISPENSING);
        $this->assertSame('completed', QueueTicket::STATUS_COMPLETED);
        $this->assertSame('no_show', QueueTicket::STATUS_NO_SHOW);
    }

    public function test_frame_low_stock_threshold_helper(): void
    {
        $frameHealthy = new Frame([
            'stock_quantity' => 10,
            'reorder_threshold' => 4,
        ]);
        $this->assertFalse($frameHealthy->isLowStock());

        $frameAtThreshold = new Frame([
            'stock_quantity' => 4,
            'reorder_threshold' => 4,
        ]);
        $this->assertTrue($frameAtThreshold->isLowStock());

        $frameCritical = new Frame([
            'stock_quantity' => 1,
            'reorder_threshold' => 5,
        ]);
        $this->assertTrue($frameCritical->isLowStock());
    }

    public function test_clinic_services_duration_and_pricing(): void
    {
        $service = new ClinicService([
            'name' => 'Contact Lens Evaluation',
            'duration_minutes' => 45,
            'fee' => 1200.00,
            'category' => 'clinical',
            'is_active' => true,
        ]);

        $this->assertSame(45, $service->duration_minutes);
        $this->assertSame(1200.00, (float) $service->fee);
        $this->assertTrue($service->is_active);
    }

    public function test_recall_command_runs_when_db_available(): void
    {
        if (! extension_loaded('pdo_sqlite') && config('database.default') === 'sqlite') {
            $this->markTestSkipped('pdo_sqlite driver not loaded in CLI environment');
        }

        $exitCode = $this->artisan('clinic:send-recalls', ['--dry-run' => true]);
        $this->assertSame(0, $exitCode);
    }
}

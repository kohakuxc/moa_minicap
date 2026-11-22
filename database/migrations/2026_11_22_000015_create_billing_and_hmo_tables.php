<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hmo_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('code', 50)->unique();
            $table->string('contact_email', 120)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->text('coverage_terms')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('invoice_number', 40)->unique();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('lab_order_id')->nullable()->constrained('lab_orders')->nullOnDelete();
            $table->foreignId('hmo_provider_id')->nullable()->constrained('hmo_providers')->nullOnDelete();
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('hmo_covered_amount', 10, 2)->default(0.00);
            $table->decimal('patient_payable_amount', 10, 2)->default(0.00);
            $table->decimal('total_paid', 10, 2)->default(0.00);
            $table->string('payment_method', 40)->default('cash');
            $table->string('payment_status', 30)->default('unpaid')->index();
            $table->text('refund_reason')->nullable();
            $table->foreignId('refund_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
        });

        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('item_type', 50)->default('service');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('description', 200);
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0.00);
            $table->decimal('line_total', 10, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('hmo_claims', function (Blueprint $table): void {
            $table->id();
            $table->string('claim_number', 40)->unique();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('hmo_provider_id')->constrained('hmo_providers')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->string('member_id', 100);
            $table->string('approval_code', 100)->nullable();
            $table->decimal('claimed_amount', 10, 2)->default(0.00);
            $table->decimal('approved_amount', 10, 2)->default(0.00);
            $table->string('status', 30)->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();

            $table->index(['hmo_provider_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hmo_claims');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('hmo_providers');
    }
};

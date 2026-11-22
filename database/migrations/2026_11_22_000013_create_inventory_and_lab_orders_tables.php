<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frames', function (Blueprint $table): void {
            $table->id();
            $table->string('sku', 60)->unique();
            $table->string('brand', 100);
            $table->string('model_name', 120);
            $table->string('category', 50)->default('spectacle');
            $table->string('shape', 50)->default('rectangular');
            $table->string('material', 50)->default('acetate');
            $table->string('color', 50)->default('black');
            $table->string('gender', 30)->default('unisex');
            $table->unsignedSmallInteger('eye_size')->default(52);
            $table->unsignedSmallInteger('bridge_size')->default(18);
            $table->unsignedSmallInteger('temple_length')->default(140);
            $table->decimal('retail_price', 10, 2)->default(0.00);
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->integer('reorder_threshold')->default(5);
            $table->string('supplier_name', 120)->nullable();
            $table->boolean('is_consignment')->default(false);
            $table->string('image_url', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['brand', 'category', 'is_active']);
            $table->index(['shape', 'material', 'gender']);
        });

        Schema::create('lens_catalogs', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('index', 20)->default('1.56');
            $table->string('type', 50)->default('single_vision');
            $table->string('coating', 120)->default('anti-reflective');
            $table->decimal('price', 10, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lab_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('prescription_id')->nullable()->constrained('prescriptions')->nullOnDelete();
            $table->foreignId('frame_id')->nullable()->constrained('frames')->nullOnDelete();
            $table->foreignId('lens_catalog_id')->nullable()->constrained('lens_catalogs')->nullOnDelete();
            $table->foreignId('optician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('lab_vendor', 120)->default('In-House Edging');
            $table->string('status', 40)->default('ordered')->index();
            $table->date('estimated_completion_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('lab_notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });

        Schema::create('try_on_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('frame_id')->constrained('frames')->cascadeOnDelete();
            $table->string('status', 30)->default('reserved');
            $table->dateTime('reserved_until');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('try_on_reservations');
        Schema::dropIfExists('lab_orders');
        Schema::dropIfExists('lens_catalogs');
        Schema::dropIfExists('frames');
    }
};

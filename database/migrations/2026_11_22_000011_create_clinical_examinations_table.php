<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_examinations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('optometrist_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->text('chief_complaint')->nullable();
            $table->string('visual_acuity_od', 50)->nullable();
            $table->string('visual_acuity_os', 50)->nullable();
            $table->decimal('sph_od', 5, 2)->nullable();
            $table->decimal('cyl_od', 5, 2)->nullable();
            $table->unsignedSmallInteger('axis_od')->nullable();
            $table->decimal('add_od', 4, 2)->nullable();
            $table->decimal('sph_os', 5, 2)->nullable();
            $table->decimal('cyl_os', 5, 2)->nullable();
            $table->unsignedSmallInteger('axis_os')->nullable();
            $table->decimal('add_os', 4, 2)->nullable();
            $table->decimal('pd', 5, 2)->nullable();
            $table->decimal('iop_od', 5, 2)->nullable();
            $table->decimal('iop_os', 5, 2)->nullable();
            $table->text('slit_lamp_notes')->nullable();
            $table->text('fundus_notes')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('management_plan')->nullable();
            $table->string('digital_signature_token', 64)->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
            $table->index(['optometrist_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_examinations');
    }
};

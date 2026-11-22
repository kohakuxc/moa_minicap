<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('service_id')->nullable()->after('doctor_id')->constrained('clinic_services')->nullOnDelete();
            $table->string('exam_room', 60)->nullable()->after('specialist');
            $table->unsignedSmallInteger('duration_minutes')->default(30)->after('starts_at');
            $table->dateTime('checked_in_at')->nullable()->after('status');
            $table->string('check_in_token', 20)->nullable()->after('checked_in_at');
        });

        Schema::create('digital_intakes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->text('medical_history')->nullable();
            $table->text('symptoms')->nullable();
            $table->text('ocular_history')->nullable();
            $table->text('current_medications')->nullable();
            $table->text('allergies')->nullable();
            $table->string('hmo_provider_name', 120)->nullable();
            $table->string('hmo_member_id', 100)->nullable();
            $table->string('preferred_eyewear', 60)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_intakes');
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign(['service_id']);
            $table->dropColumn(['service_id', 'exam_room', 'duration_minutes', 'checked_in_at', 'check_in_token']);
        });
    }
};

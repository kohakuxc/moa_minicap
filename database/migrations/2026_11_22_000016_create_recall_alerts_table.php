<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recall_alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->string('recall_type', 40)->default('annual_exam');
            $table->date('due_date');
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('last_notified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'due_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recall_alerts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('doctor_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('reason', 1000)->nullable()->after('notes');
            $table->index(['doctor_id', 'starts_at', 'status']);
        });

        Schema::create('doctor_availabilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->index(['doctor_id', 'starts_at', 'is_available']);
        });

        Schema::create('appointment_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_notes');
        Schema::dropIfExists('doctor_availabilities');
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign(['doctor_id']);
            $table->dropIndex(['doctor_id', 'starts_at', 'status']);
            $table->dropColumn(['doctor_id', 'reason']);
        });
    }
};

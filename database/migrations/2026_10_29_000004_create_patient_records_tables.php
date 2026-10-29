<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('service', 100);
            $table->string('specialist', 120)->nullable();
            $table->dateTime('starts_at');
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('requested')->index();
            $table->timestamps();
            $table->index(['starts_at', 'status']);
        });

        Schema::create('prescriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('eye', 2);
            $table->decimal('sphere', 5, 2)->nullable();
            $table->decimal('cylinder', 5, 2)->nullable();
            $table->unsignedSmallInteger('axis')->nullable();
            $table->decimal('pd', 5, 2)->nullable();
            $table->date('issued_at');
            $table->date('expires_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('appointments');
    }
};

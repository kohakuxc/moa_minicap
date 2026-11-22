<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->foreignId('examination_id')->nullable()->after('user_id')->constrained('clinical_examinations')->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->after('examination_id')->constrained('users')->nullOnDelete();
            $table->string('rx_type', 30)->default('spectacle')->after('eye');
            $table->decimal('add', 4, 2)->nullable()->after('pd');
            $table->text('notes')->nullable()->after('add');
            $table->string('signature_token', 64)->nullable()->after('notes');
            $table->boolean('is_verified')->default(true)->after('signature_token');
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->dropForeign(['examination_id']);
            $table->dropForeign(['doctor_id']);
            $table->dropColumn(['examination_id', 'doctor_id', 'rx_type', 'add', 'notes', 'signature_token', 'is_verified']);
        });
    }
};

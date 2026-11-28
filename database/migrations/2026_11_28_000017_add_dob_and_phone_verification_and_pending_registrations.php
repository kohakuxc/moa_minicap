<?php

use App\Support\IdentityNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'canonical_email')) {
                $table->string('canonical_email')->nullable()->unique()->after('email');
            }
            if (! Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('users', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            }
        });

        // Backfill canonical_email and normalized phones safely for existing rows
        DB::table('users')
            ->select(['id', 'email', 'phone'])
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $canonicalEmail = IdentityNormalizer::canonicalEmail((string) $row->email);
                    $normalizedPhone = IdentityNormalizer::normalizePhone($row->phone);

                    $emailCollision = DB::table('users')
                        ->where('canonical_email', $canonicalEmail)
                        ->where('id', '!=', $row->id)
                        ->exists();

                    $updates = [];
                    if (! $emailCollision) {
                        $updates['canonical_email'] = $canonicalEmail;
                    }

                    if ($normalizedPhone !== null && $normalizedPhone !== $row->phone) {
                        $phoneCollision = DB::table('users')
                            ->where('phone', $normalizedPhone)
                            ->where('id', '!=', $row->id)
                            ->exists();

                        if (! $phoneCollision) {
                            $updates['phone'] = $normalizedPhone;
                        }
                    }

                    if ($updates !== []) {
                        DB::table('users')->where('id', $row->id)->update($updates);
                    }
                }
            });

        if (! Schema::hasTable('pending_registrations')) {
            Schema::create('pending_registrations', function (Blueprint $table): void {
                $table->id();
                $table->string('token', 64)->unique();
                $table->string('name', 120);
                $table->string('email', 255);
                $table->string('canonical_email', 255)->index();
                $table->string('phone', 30)->index();
                $table->date('date_of_birth');
                $table->string('password');
                $table->string('email_token_hash', 64);
                $table->timestamp('email_verified_at')->nullable();
                $table->string('phone_otp_hash');
                $table->timestamp('phone_otp_expires_at');
                $table->unsignedSmallInteger('phone_otp_attempts')->default(0);
                $table->timestamp('phone_verified_at')->nullable();
                $table->timestamp('last_sms_sent_at')->nullable();
                $table->timestamp('last_email_sent_at')->nullable();
                $table->timestamp('expires_at')->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_registrations');

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'canonical_email')) {
                $table->dropUnique(['canonical_email']);
                $table->dropColumn('canonical_email');
            }
            if (Schema::hasColumn('users', 'date_of_birth')) {
                $table->dropColumn('date_of_birth');
            }
            if (Schema::hasColumn('users', 'phone_verified_at')) {
                $table->dropColumn('phone_verified_at');
            }
        });
    }
};

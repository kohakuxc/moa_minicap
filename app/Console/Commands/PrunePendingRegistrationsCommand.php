<?php

namespace App\Console\Commands;

use App\Models\PendingRegistration;
use Illuminate\Console\Command;

class PrunePendingRegistrationsCommand extends Command
{
    protected $signature = 'registrations:prune';

    protected $description = 'Delete expired pending registrations that never completed verification.';

    public function handle(): int
    {
        $deleted = PendingRegistration::where('expires_at', '<', now())->delete();

        $this->info("Pruned {$deleted} expired pending registration(s).");

        return self::SUCCESS;
    }
}

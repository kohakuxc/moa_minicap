<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteUser extends Command
{
    protected $signature = 'user:promote {email}';
    protected $description = 'Promote an existing user to administrator';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('No user found with that email.');
            return self::FAILURE;
        }
        if (! $this->confirm("Promote {$user->email} to admin?")) {
            return self::SUCCESS;
        }
        $user->update(['role' => 'admin']);
        $this->info('User promoted.');
        return self::SUCCESS;
    }
}

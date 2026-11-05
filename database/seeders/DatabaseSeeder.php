<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\DoctorAvailability;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'doctor@moa.test'], [
            'name' => 'MOA Doctor',
            'role' => 'doctor',
            'phone' => '555-0100',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        User::updateOrCreate(['email' => 'patient@moa.test'], [
            'name' => 'MOA Patient',
            'role' => 'patient',
            'phone' => '555-0101',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $doctor = User::where('email', 'doctor@moa.test')->firstOrFail();
        $nextWeekday = now()->addWeek()->nextWeekday()->setTime(9, 0);
        DoctorAvailability::firstOrCreate([
            'doctor_id' => $doctor->id,
            'starts_at' => $nextWeekday,
        ], [
            'ends_at' => $nextWeekday->copy()->addHours(8),
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\ClinicService;
use App\Models\DoctorAvailability;
use App\Models\Frame;
use App\Models\HmoProvider;
use App\Models\LensCatalog;
use App\Models\User;
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
        // 1. Core Staff and Patients
        $admin = User::updateOrCreate(['email' => 'admin@moa.test'], [
            'name' => 'Dr. Adrian Miller (Clinic Director)',
            'role' => User::ROLE_ADMIN,
            'phone' => '555-0199',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'profile_completed_at' => now(),
        ]);

        $optometrist = User::updateOrCreate(['email' => 'optometrist@moa.test'], [
            'name' => 'Dr. Elena Vance, OD',
            'role' => User::ROLE_OPTOMETRIST,
            'phone' => '555-0198',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'profile_completed_at' => now(),
        ]);

        $optician = User::updateOrCreate(['email' => 'optician@moa.test'], [
            'name' => 'Marcus Chen (Dispensing Optician)',
            'role' => User::ROLE_OPTICIAN,
            'phone' => '555-0197',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'profile_completed_at' => now(),
        ]);

        $receptionist = User::updateOrCreate(['email' => 'receptionist@moa.test'], [
            'name' => 'Sarah Jenkins (Front Desk)',
            'role' => User::ROLE_RECEPTIONIST,
            'phone' => '555-0196',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'profile_completed_at' => now(),
        ]);

        // Legacy doctor account
        $doctor = User::updateOrCreate(['email' => 'doctor@moa.test'], [
            'name' => 'MOA Doctor',
            'role' => 'doctor',
            'phone' => '555-0100',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'profile_completed_at' => now(),
        ]);

        $patient = User::updateOrCreate(['email' => 'patient@moa.test'], [
            'name' => 'Jane Cooper',
            'role' => User::ROLE_PATIENT,
            'phone' => '555-0101',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'profile_completed_at' => now(),
        ]);

        // Doctor Availability
        $nextWeekday = now()->addWeek()->nextWeekday()->setTime(9, 0);
        DoctorAvailability::firstOrCreate([
            'doctor_id' => $doctor->id,
            'starts_at' => $nextWeekday,
        ], [
            'ends_at' => $nextWeekday->copy()->addHours(8),
        ]);

        DoctorAvailability::firstOrCreate([
            'doctor_id' => $optometrist->id,
            'starts_at' => $nextWeekday,
        ], [
            'ends_at' => $nextWeekday->copy()->addHours(8),
        ]);

        // 2. Standard Clinic Services
        $services = [
            [
                'name' => 'Comprehensive Eye Exam',
                'slug' => 'comprehensive-eye-exam',
                'category' => 'clinical',
                'duration_minutes' => 30,
                'fee' => 850.00,
                'description' => 'Complete visual acuity assessment, automated & subjective refraction, intraocular pressure check, and anterior/posterior segment evaluation.',
            ],
            [
                'name' => 'Contact Lens Fitting & Evaluation',
                'slug' => 'contact-lens-fitting',
                'category' => 'clinical',
                'duration_minutes' => 45,
                'fee' => 1200.00,
                'description' => 'Corneal curvature topography, tear-film stability analysis, trial lens insertion, and movement assessment.',
            ],
            [
                'name' => 'Glaucoma & Tonometry Screening',
                'slug' => 'glaucoma-screening',
                'category' => 'clinical',
                'duration_minutes' => 20,
                'fee' => 650.00,
                'description' => 'Non-contact intraocular pressure (IOP) measurement, optic nerve head observation, and risk assessment.',
            ],
            [
                'name' => 'Pediatric Vision Assessment',
                'slug' => 'pediatric-vision',
                'category' => 'clinical',
                'duration_minutes' => 40,
                'fee' => 950.00,
                'description' => 'Child-friendly binocular vision, stereopsis, amblyopia check, and color vision screening.',
            ],
            [
                'name' => 'Frame Styling & Prescription Dispensing',
                'slug' => 'frame-styling-dispensing',
                'category' => 'optical',
                'duration_minutes' => 20,
                'fee' => 0.00,
                'description' => 'Face-shape matching, bridge and temple fitting, pantoscopic tilt adjustment, and lens customization consultation.',
            ],
        ];

        foreach ($services as $serviceData) {
            ClinicService::updateOrCreate(['slug' => $serviceData['slug']], $serviceData);
        }

        // 3. Accredited HMO Providers
        $hmoProviders = [
            [
                'name' => 'MaxiCare HealthCare',
                'code' => 'MAXICARE',
                'contact_email' => 'claims@maxicare.com.ph',
                'contact_phone' => '+63 2 8582 1900',
                'coverage_terms' => 'Annual comprehensive eye examination 100% covered; optical allowance up to PHP 2,500 on accredited frames/lenses.',
            ],
            [
                'name' => 'Intellicare / Asalus',
                'code' => 'INTELLICARE',
                'contact_email' => 'providers@intellicare.net.ph',
                'contact_phone' => '+63 2 8789 4000',
                'coverage_terms' => 'Outpatient eye consultation covered with LOA; discount on lens upgrades.',
            ],
            [
                'name' => 'Medicard Philippines',
                'code' => 'MEDICARD',
                'contact_email' => 'optical@medicardphils.com',
                'contact_phone' => '+63 2 8841 8080',
                'coverage_terms' => 'Routine refraction covered once per year; frame vouchers valid on selected partners.',
            ],
            [
                'name' => 'PhilCare',
                'code' => 'PHILCARE',
                'contact_email' => 'care@philcare.com.ph',
                'contact_phone' => '+63 2 8462 1800',
                'coverage_terms' => 'Vision consultation and digital retina scan included under elite tiers.',
            ],
        ];

        foreach ($hmoProviders as $hmo) {
            HmoProvider::updateOrCreate(['code' => $hmo['code']], $hmo);
        }

        // 4. Optical Inventory: Frames
        $frames = [
            [
                'sku' => 'MOA-AT-001',
                'brand' => 'Aether Titanium',
                'model_name' => 'Komorebi Minimalist',
                'category' => 'spectacle',
                'shape' => 'round',
                'material' => 'titanium',
                'color' => 'Matte Bronze',
                'gender' => 'unisex',
                'eye_size' => 49,
                'bridge_size' => 20,
                'temple_length' => 142,
                'retail_price' => 4850.00,
                'cost_price' => 2200.00,
                'stock_quantity' => 12,
                'reorder_threshold' => 4,
                'supplier_name' => 'Kyoto Optical Craft',
                'image_url' => '/asset/img/reading.png',
            ],
            [
                'sku' => 'MOA-AT-002',
                'brand' => 'Aether Titanium',
                'model_name' => 'Aero Square',
                'category' => 'spectacle',
                'shape' => 'rectangular',
                'material' => 'titanium',
                'color' => 'Brushed Gunmetal',
                'gender' => 'mens',
                'eye_size' => 54,
                'bridge_size' => 17,
                'temple_length' => 145,
                'retail_price' => 5200.00,
                'cost_price' => 2400.00,
                'stock_quantity' => 8,
                'reorder_threshold' => 3,
                'supplier_name' => 'Kyoto Optical Craft',
                'image_url' => '/asset/img/prescription.png',
            ],
            [
                'sku' => 'MOA-AC-101',
                'brand' => 'Mazzuchelli Artisan',
                'model_name' => 'Amber Tortoise Heritage',
                'category' => 'spectacle',
                'shape' => 'cat-eye',
                'material' => 'acetate',
                'color' => 'Amber Havana',
                'gender' => 'womens',
                'eye_size' => 51,
                'bridge_size' => 18,
                'temple_length' => 140,
                'retail_price' => 3950.00,
                'cost_price' => 1800.00,
                'stock_quantity' => 15,
                'reorder_threshold' => 5,
                'supplier_name' => 'Venezia Eyewear SpA',
                'image_url' => '/asset/img/sunglasses.png',
            ],
            [
                'sku' => 'MOA-SG-201',
                'brand' => 'Solaris Polarized',
                'model_name' => 'Aviator Titanium Gold',
                'category' => 'sunglasses',
                'shape' => 'aviator',
                'material' => 'titanium',
                'color' => 'Champagne Gold / Green G-15',
                'gender' => 'unisex',
                'eye_size' => 58,
                'bridge_size' => 14,
                'temple_length' => 140,
                'retail_price' => 6400.00,
                'cost_price' => 2900.00,
                'stock_quantity' => 6,
                'reorder_threshold' => 3,
                'supplier_name' => 'Solaris Global',
                'image_url' => '/asset/img/sunglasses.png',
            ],
            [
                'sku' => 'MOA-AC-105',
                'brand' => 'Nordic Ultem',
                'model_name' => 'Featherweight Urban',
                'category' => 'spectacle',
                'shape' => 'geometric',
                'material' => 'ultem',
                'color' => 'Translucent Smoke',
                'gender' => 'unisex',
                'eye_size' => 50,
                'bridge_size' => 19,
                'temple_length' => 142,
                'retail_price' => 2800.00,
                'cost_price' => 1100.00,
                'stock_quantity' => 3, // Low stock for alerts
                'reorder_threshold' => 5,
                'supplier_name' => 'ScandiVision Supplies',
                'image_url' => '/asset/img/reading.png',
            ],
        ];

        foreach ($frames as $frameData) {
            Frame::updateOrCreate(['sku' => $frameData['sku']], $frameData);
        }

        // 5. Standard Optical Lens Catalog
        $lenses = [
            [
                'name' => 'Standard Single Vision 1.56 UV400 HMC',
                'index' => '1.56',
                'type' => 'single_vision',
                'coating' => 'Hard Multi-Coated Anti-Reflective',
                'price' => 1200.00,
            ],
            [
                'name' => 'High-Index 1.60 Blue-Shield Digital Filter',
                'index' => '1.60',
                'type' => 'single_vision',
                'coating' => 'Blue-Light Absorbing + Hydrophobic AR',
                'price' => 2400.00,
            ],
            [
                'name' => 'Ultra-Thin 1.67 Aspheric Photochromic Transitions',
                'index' => '1.67',
                'type' => 'single_vision',
                'coating' => 'Rapid Light-Adaptive UV420',
                'price' => 4500.00,
            ],
            [
                'name' => 'Digital Freeform Progressive 1.60 Wide-Corridor',
                'index' => '1.60',
                'type' => 'progressive',
                'coating' => 'Premium Anti-Scratch + Glare-Reduction',
                'price' => 6800.00,
            ],
        ];

        foreach ($lenses as $lensData) {
            LensCatalog::updateOrCreate(['name' => $lensData['name']], $lensData);
        }
    }
}

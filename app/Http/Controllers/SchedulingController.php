<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ClinicService;
use App\Models\DoctorAvailability;
use App\Models\DoctorLeaveBlock;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SchedulingController extends Controller
{
    public function index(Request $request)
    {
        $doctors = User::whereIn('role', [User::ROLE_OPTOMETRIST, User::ROLE_DOCTOR, User::ROLE_ADMIN])->get();
        $availabilities = DoctorAvailability::with('doctor')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->get();
        $leaveBlocks = DoctorLeaveBlock::with('doctor')
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')
            ->get();
        $services = ClinicService::orderBy('category')->orderBy('name')->get();

        $examRooms = [
            'Room 1 - Comprehensive Refraction Suite',
            'Room 2 - Contact Lens Diagnostic Lab',
            'Room 3 - Anterior & Glaucoma Diagnostics',
            'Room 4 - Pediatric & Binocular Vision',
        ];

        return view('admin.scheduling', [
            'doctors' => $doctors,
            'availabilities' => $availabilities,
            'leaveBlocks' => $leaveBlocks,
            'services' => $services,
            'examRooms' => $examRooms,
        ]);
    }

    public function storeLeaveBlock(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => ['required', 'exists:users,id'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $leave = DoctorLeaveBlock::create($validated);
        AuditLog::record($request->user(), 'doctor.leave_block_added', $leave);

        return back()->with('success', 'Doctor leave block registered. Affected appointment slots will be held.');
    }

    public function destroyLeaveBlock(Request $request, DoctorLeaveBlock $leaveBlock)
    {
        AuditLog::record($request->user(), 'doctor.leave_block_removed', $leaveBlock);
        $leaveBlock->delete();

        return back()->with('success', 'Doctor leave block removed.');
    }

    public function storeService(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'in:clinical,optical,consultation'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:180'],
            'fee' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $slug = Str::slug($validated['name']);
        $service = ClinicService::updateOrCreate(
            ['slug' => $slug],
            array_merge($validated, ['is_active' => true])
        );

        AuditLog::record($request->user(), 'clinic_service.saved', $service);

        return back()->with('success', "Service '{$service->name}' saved ({$service->duration_minutes} min, PHP {$service->fee}).");
    }

    public function toggleService(Request $request, ClinicService $service)
    {
        $service->is_active = ! $service->is_active;
        $service->save();

        AuditLog::record($request->user(), 'clinic_service.toggled:'.$service->id, $service);

        return back()->with('success', "Service '{$service->name}' is now " . ($service->is_active ? 'active' : 'inactive') . '.');
    }
}

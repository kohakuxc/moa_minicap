<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StaffManagementController extends Controller
{
    /**
     * Display the list of staff and administrator accounts.
     */
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403, 'Administrator privileges are required to manage clinic staff accounts.');

        $staffRoles = [
            User::ROLE_ADMIN,
            User::ROLE_DOCTOR,
            User::ROLE_OPTOMETRIST,
            User::ROLE_OPTICIAN,
            User::ROLE_RECEPTIONIST,
        ];

        $staffMembers = User::whereIn('role', $staffRoles)
            ->orderByRaw("
                CASE 
                    WHEN email = ? THEN 1
                    WHEN role = 'admin' THEN 2
                    WHEN role = 'doctor' THEN 3
                    WHEN role = 'optometrist' THEN 4
                    WHEN role = 'optician' THEN 5
                    WHEN role = 'receptionist' THEN 6
                    ELSE 7
                END
            ", [User::ORIGINAL_ADMIN_EMAIL])
            ->orderBy('name')
            ->get();

        $stats = [
            'total_staff' => $staffMembers->count(),
            'total_admins' => $staffMembers->where('role', User::ROLE_ADMIN)->count(),
            'total_clinical' => $staffMembers->whereIn('role', [User::ROLE_DOCTOR, User::ROLE_OPTOMETRIST])->count(),
            'total_dispensing' => $staffMembers->where('role', User::ROLE_OPTICIAN)->count(),
            'total_reception' => $staffMembers->where('role', User::ROLE_RECEPTIONIST)->count(),
        ];

        return view('admin.staff', [
            'staffMembers' => $staffMembers,
            'stats' => $stats,
            'currentUser' => $request->user(),
        ]);
    }

    /**
     * Register a new administrator or staff member.
     */
    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403, 'Administrator privileges required.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_DOCTOR,
                User::ROLE_OPTOMETRIST,
                User::ROLE_OPTICIAN,
                User::ROLE_RECEPTIONIST,
            ])],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $newUser = User::create([
            'name' => Str::squish($validated['name']),
            'email' => Str::lower(trim($validated['email'])),
            'phone' => $validated['phone'] ? trim($validated['phone']) : null,
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'profile_completed_at' => now(),
        ]);

        AuditLog::record($request->user(), 'staff.created:'.$newUser->role, $newUser);

        return back()->with('success', "New account for \"{$newUser->name}\" created with role \"".ucfirst($newUser->role)."\".");
    }

    /**
     * Update an existing administrator or staff member's information.
     */
    public function update(Request $request, User $user)
    {
        abort_unless($request->user()->isAdmin(), 403, 'Administrator privileges required.');

        // Invariant: The original admin (doctor.demo@moa.local) cannot be demoted or have their email changed
        if ($user->isOriginalAdmin()) {
            if ($request->input('role') !== User::ROLE_ADMIN) {
                return back()->withErrors(['role' => 'The original administrator account (doctor.demo@moa.local) cannot be demoted from the Admin role.']);
            }
            if (Str::lower(trim($request->input('email'))) !== User::ORIGINAL_ADMIN_EMAIL) {
                return back()->withErrors(['email' => 'The email address for the original administrator (doctor.demo@moa.local) is permanent and cannot be modified.']);
            }
        }

        // Prevent sole remaining admin from demoting themselves
        if ($request->user()->id === $user->id && $request->input('role') !== User::ROLE_ADMIN) {
            $otherAdmins = User::where('role', User::ROLE_ADMIN)->where('id', '!=', $user->id)->count();
            if ($otherAdmins === 0) {
                return back()->withErrors(['role' => 'You cannot remove your own administrator status while you are the only active admin.']);
            }
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_DOCTOR,
                User::ROLE_OPTOMETRIST,
                User::ROLE_OPTICIAN,
                User::ROLE_RECEPTIONIST,
            ])],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $user->name = Str::squish($validated['name']);
        if (! $user->isOriginalAdmin()) {
            $user->email = Str::lower(trim($validated['email']));
            $user->role = $validated['role'];
        }
        $user->phone = $validated['phone'] ? trim($validated['phone']) : null;

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        AuditLog::record($request->user(), 'staff.updated', $user);

        return back()->with('success', "Information for \"{$user->name}\" updated successfully.");
    }

    /**
     * Revoke staff privileges and transition account to patient role.
     */
    public function revoke(Request $request, User $user)
    {
        abort_unless($request->user()->isAdmin(), 403, 'Administrator privileges required.');

        if ($user->isOriginalAdmin()) {
            return back()->withErrors(['error' => 'The original administrator account (doctor.demo@moa.local) cannot be revoked or demoted.']);
        }

        if ($request->user()->id === $user->id) {
            return back()->withErrors(['error' => 'You cannot revoke your own administrator account.']);
        }

        $user->update(['role' => User::ROLE_PATIENT]);

        AuditLog::record($request->user(), 'staff.revoked', $user);

        return back()->with('success', "Staff access revoked for \"{$user->name}\". Account transitioned to Patient role.");
    }

    /**
     * Display the current administrator's self-profile edit form.
     */
    public function profile(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403, 'Administrator privileges required.');

        return view('admin.profile', [
            'adminUser' => $request->user(),
        ]);
    }

    /**
     * Update the logged-in administrator's own profile and password.
     */
    public function updateProfile(Request $request)
    {
        $admin = $request->user();
        abort_unless($admin->isAdmin(), 403, 'Administrator privileges required.');

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];

        if (! $admin->isOriginalAdmin()) {
            $rules['email'] = ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)];
        }

        if ($request->filled('new_password')) {
            $rules['current_password'] = ['required', 'current_password'];
            $rules['new_password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        $validated = $request->validate($rules);

        $admin->name = Str::squish($validated['name']);
        $admin->phone = $validated['phone'] ? trim($validated['phone']) : null;

        if (! $admin->isOriginalAdmin() && isset($validated['email'])) {
            $admin->email = Str::lower(trim($validated['email']));
        }

        if ($request->filled('new_password')) {
            $admin->password = Hash::make($validated['new_password']);
        }

        $admin->save();

        AuditLog::record($admin, 'admin.profile_updated', $admin);

        return back()->with('success', 'Your administrator profile details have been saved successfully.');
    }
}

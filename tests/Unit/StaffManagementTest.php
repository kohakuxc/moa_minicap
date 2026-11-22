<?php

namespace Tests\Unit;

use App\Http\Controllers\StaffManagementController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    protected User $originalAdmin;
    protected User $secondaryAdmin;
    protected User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalAdmin = new User([
            'name' => 'Dr. MOA Demo',
            'email' => User::ORIGINAL_ADMIN_EMAIL,
            'role' => User::ROLE_ADMIN,
        ]);
        $this->originalAdmin->id = 1;

        $this->secondaryAdmin = new User([
            'name' => 'Secondary Admin',
            'email' => 'admin2@moa.test',
            'role' => User::ROLE_ADMIN,
        ]);
        $this->secondaryAdmin->id = 2;

        $this->receptionist = new User([
            'name' => 'Sarah Reception',
            'email' => 'sarah@moa.test',
            'role' => User::ROLE_RECEPTIONIST,
        ]);
        $this->receptionist->id = 3;
    }

    public function test_original_admin_is_identified_accurately(): void
    {
        $this->assertTrue($this->originalAdmin->isOriginalAdmin());
        $this->assertFalse($this->secondaryAdmin->isOriginalAdmin());
        $this->assertFalse($this->receptionist->isOriginalAdmin());
    }

    public function test_original_admin_is_case_and_whitespace_insensitive(): void
    {
        $adminUpper = new User(['email' => '  DOCTOR.DEMO@MOA.LOCAL  ', 'role' => User::ROLE_ADMIN]);
        $this->assertTrue($adminUpper->isOriginalAdmin());
    }

    public function test_original_admin_cannot_be_demoted(): void
    {
        $controller = new StaffManagementController();

        $request = Request::create('/admin/staff/1', 'PATCH', [
            'name' => 'Dr. MOA Demo',
            'email' => User::ORIGINAL_ADMIN_EMAIL,
            'role' => User::ROLE_RECEPTIONIST, // Attempting to demote
        ]);
        $request->setUserResolver(fn () => $this->secondaryAdmin);

        $response = $controller->update($request, $this->originalAdmin);

        $this->assertTrue(session()->has('errors'));
        $errors = session()->get('errors')->getBag('default');
        $this->assertTrue($errors->has('role'));
        $this->assertStringContainsString('cannot be demoted', $errors->first('role'));
    }

    public function test_original_admin_email_cannot_be_changed(): void
    {
        $controller = new StaffManagementController();

        $request = Request::create('/admin/staff/1', 'PATCH', [
            'name' => 'Dr. MOA Demo',
            'email' => 'hacked.email@moa.test', // Attempting to change email
            'role' => User::ROLE_ADMIN,
        ]);
        $request->setUserResolver(fn () => $this->secondaryAdmin);

        $response = $controller->update($request, $this->originalAdmin);

        $this->assertTrue(session()->has('errors'));
        $errors = session()->get('errors')->getBag('default');
        $this->assertTrue($errors->has('email'));
        $this->assertStringContainsString('permanent and cannot be modified', $errors->first('email'));
    }

    public function test_original_admin_cannot_be_revoked(): void
    {
        $controller = new StaffManagementController();

        $request = Request::create('/admin/staff/1/revoke', 'PATCH');
        $request->setUserResolver(fn () => $this->secondaryAdmin);

        $response = $controller->revoke($request, $this->originalAdmin);

        $this->assertTrue(session()->has('errors'));
        $errors = session()->get('errors')->getBag('default');
        $this->assertTrue($errors->has('error'));
        $this->assertStringContainsString('cannot be revoked', $errors->first('error'));
    }

    public function test_admin_cannot_revoke_himself(): void
    {
        $controller = new StaffManagementController();

        $request = Request::create('/admin/staff/2/revoke', 'PATCH');
        $request->setUserResolver(fn () => $this->secondaryAdmin);

        $response = $controller->revoke($request, $this->secondaryAdmin);

        $this->assertTrue(session()->has('errors'));
        $errors = session()->get('errors')->getBag('default');
        $this->assertTrue($errors->has('error'));
        $this->assertStringContainsString('cannot revoke your own', $errors->first('error'));
    }

    public function test_staff_hierarchy_rank_ordering(): void
    {
        $users = collect([
            new User(['name' => 'Des', 'email' => 'reception@moa.test', 'role' => User::ROLE_RECEPTIONIST]),
            new User(['name' => 'Optician Bengkong', 'email' => 'optician@moa.test', 'role' => User::ROLE_OPTICIAN]),
            new User(['name' => 'Dr. Opto', 'email' => 'opto@moa.test', 'role' => User::ROLE_OPTOMETRIST]),
            new User(['name' => 'MOA Doctor', 'email' => 'doc@moa.test', 'role' => User::ROLE_DOCTOR]),
            new User(['name' => 'Dr. Admin', 'email' => 'admin@moa.test', 'role' => User::ROLE_ADMIN]),
            new User(['name' => 'Dr. MOA Demo', 'email' => User::ORIGINAL_ADMIN_EMAIL, 'role' => User::ROLE_ADMIN]),
        ]);

        $getHierarchyRank = function (User $u): int {
            if ($u->isOriginalAdmin()) return 1;
            return match ($u->role) {
                User::ROLE_ADMIN => 2,
                User::ROLE_DOCTOR => 3,
                User::ROLE_OPTOMETRIST => 4,
                User::ROLE_OPTICIAN => 5,
                User::ROLE_RECEPTIONIST => 6,
                default => 7,
            };
        };

        $sorted = $users->sort(function (User $a, User $b) use ($getHierarchyRank) {
            $rankDiff = $getHierarchyRank($a) <=> $getHierarchyRank($b);
            return $rankDiff !== 0 ? $rankDiff : strcmp($a->name, $b->name);
        })->values();

        $this->assertSame(User::ORIGINAL_ADMIN_EMAIL, $sorted[0]->email);
        $this->assertSame('Dr. Admin', $sorted[1]->name);
        $this->assertSame('MOA Doctor', $sorted[2]->name);
        $this->assertSame('Dr. Opto', $sorted[3]->name);
        $this->assertSame('Optician Bengkong', $sorted[4]->name);
        $this->assertSame('Des', $sorted[5]->name);
    }
}

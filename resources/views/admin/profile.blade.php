@extends('admin.layout')

@section('title', 'My Administrator Profile')

@section('content')
<div class="admin-page-head">
    <div>
        <h1>My administrator <em>profile & security.</em></h1>
        <p>Manage your executive administrative details, contact information, and account security credentials.</p>
    </div>
</div>

@if(session('success'))
    <p class="portal-feedback" role="status" style="border-left: 4px solid var(--moa-teal); background: rgba(100, 188, 180, 0.12); color: #164e63;">
        <i class="bx bx-check-circle"></i> {{ session('success') }}
    </p>
@endif

@if($errors->any())
    <div class="admin-validation" role="alert" style="margin-bottom: 20px;">
        <strong>Please check your profile inputs:</strong>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 24px;">
    <!-- Profile & Credentials Form -->
    <section class="admin-panel" style="margin-top: 0;">
        <div class="admin-section-head">
            <div>
                <h2>Account details & credentials</h2>
                <p>Ensure your contact details and clinic notifications are up to date.</p>
            </div>
            @if($adminUser->isOriginalAdmin())
                <span class="staff-role-badge" style="background: rgba(240, 186, 127, 0.4); color: #7A5530; border: 1px solid var(--moa-gold);">
                    <i class="bx bxs-badge-check"></i> Original Admin
                </span>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.profile.update') }}" style="display: flex; flex-direction: column; gap: 16px;">
            @csrf
            @method('PATCH')

            <label style="display: flex; flex-direction: column; gap: 5px; font-weight: 600; font-size: 13px;">
                <span class="required">Full Name</span>
                <input type="text" name="name" value="{{ old('name', $adminUser->name) }}" required style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
            </label>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <label style="display: flex; flex-direction: column; gap: 5px; font-weight: 600; font-size: 13px;">
                    <span class="required">Email Address</span>
                    @if($adminUser->isOriginalAdmin())
                        <input type="email" value="{{ $adminUser->email }}" readonly style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px; background-color: #f5f0ea; color: #555;">
                        <small style="color: var(--moa-bronze); font-size: 11px;">* Original administrator primary email cannot be modified.</small>
                    @else
                        <input type="email" name="email" value="{{ old('email', $adminUser->email) }}" required style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
                    @endif
                </label>

                <label style="display: flex; flex-direction: column; gap: 5px; font-weight: 600; font-size: 13px;">
                    Phone Number
                    <input type="text" name="phone" value="{{ old('phone', $adminUser->phone) }}" placeholder="e.g. 555-0199" style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
                </label>
            </div>

            <!-- Role Badge Display -->
            <div style="background: #FAF8F5; border: 1px solid #EAE4DC; border-radius: 8px; padding: 12px 14px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: 12px; color: var(--moa-slate); font-weight: 600;">System Access Level:</div>
                    <strong style="color: var(--moa-bronze); font-size: 14px;">{{ ucfirst($adminUser->role) }} Privilege Tier</strong>
                </div>
                <span class="staff-role-badge">Verified System Officer</span>
            </div>

            <!-- Password Change Section -->
            <div style="border-top: 1px solid #EAE4DC; padding-top: 18px; margin-top: 6px;">
                <div style="font-weight: 700; font-size: 14px; color: var(--moa-bronze); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                    <i class="bx bx-lock-alt"></i> Change Password
                </div>
                <p style="font-size: 12px; color: var(--moa-slate); margin: 0 0 14px;">
                    Leave password fields blank if you do not wish to change your current password.
                </p>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                        Current Password
                        <input type="password" name="current_password" placeholder="Verify your current password" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
                    </label>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                            New Password
                            <input type="password" name="new_password" placeholder="Minimum 8 characters" minlength="8" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
                        </label>

                        <label style="display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600;">
                            Confirm New Password
                            <input type="password" name="new_password_confirmation" placeholder="Repeat new password" minlength="8" style="padding: 8px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
                        </label>
                    </div>
                </div>
            </div>

            <div style="margin-top: 10px; display: flex; justify-content: flex-end;">
                <button type="submit" class="button button-teal button-primary" style="padding: 10px 24px;">
                    <i class="bx bx-check"></i> Save Changes
                </button>
            </div>
        </form>
    </section>

    <!-- Account Overview & Clinic Privileges Aside -->
    <aside style="display: flex; flex-direction: column; gap: 20px;">
        <div class="portal-panel" style="padding: 24px; background: #fff; border: 1px solid #E5DFD7; border-radius: 12px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
                <span class="patient-avatar" style="background: #9F652C; color: #fff; font-size: 18px; width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%;">
                    {{ strtoupper(substr($adminUser->name, 0, 1)) }}
                </span>
                <div>
                    <strong style="font-size: 16px;">{{ $adminUser->name }}</strong>
                    <div style="font-size: 12px; color: var(--moa-slate);">{{ $adminUser->email }}</div>
                </div>
            </div>

            <h3 style="font-size: 14px; margin: 16px 0 8px; color: var(--moa-bronze);">Your Administrative Capabilities</h3>
            <ul style="padding-left: 18px; font-size: 13px; color: #4A4540; line-height: 1.6; margin: 0 0 16px;">
                <li>Provision and manage other administrator and doctor accounts.</li>
                <li>Access the live EHR clinical suite and sign digital refraction certificates.</li>
                <li>Reconcile daily billing transactions and authorize optical refunds.</li>
                <li>Oversee optical lab order pipeline, queue boards, and doctor scheduling.</li>
            </ul>

            <div style="background: rgba(100, 188, 180, 0.12); border-radius: 8px; padding: 12px 14px; font-size: 12px; color: #164e63;">
                <i class="bx bx-shield-check"></i> <strong>Audit Log Security:</strong> All staff creations, profile edits, and role adjustments are permanently stamped with your actor token in the clinic audit trail.
            </div>
        </div>
    </aside>
</div>
@endsection

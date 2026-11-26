@extends('admin.layout')

@section('title', 'Staff & Administrator Governance')

@section('content')
<div class="admin-page-head">
    <div>
        <h1>Clinic staff & <em>admin governance.</em></h1>
        <p>Register accredited clinic personnel, grant administrative access, update staff credentials, and enforce role permissions.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <button type="button" class="button button-teal" onclick="document.getElementById('add-staff-modal').hidden = false;">
            <i class="bx bx-user-plus"></i> Add Staff / Admin
        </button>
    </div>
</div>

@if(session('success'))
    <p class="portal-feedback" role="status" style="border-left: 4px solid var(--moa-teal); background: rgba(100, 188, 180, 0.12); color: #164e63;">
        <i class="bx bx-check-circle"></i> {{ session('success') }}
    </p>
@endif

@if($errors->any())
    <div class="admin-validation" role="alert" style="margin-bottom: 20px;">
        <strong>Please check staff inputs:</strong>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<!-- Staff Metric KPI Cards -->
<div class="kpi-grid-5" style="margin-bottom: 24px;">
    <div class="kpi-card-custom kpi-appointments">
        <div class="kpi-header"><span>Total Staff</span><i class="bx bx-group"></i></div>
        <div class="kpi-value">{{ $stats['total_staff'] }}</div>
        <div class="kpi-sub">Authorized clinic personnel</div>
    </div>
    <div class="kpi-card-custom kpi-revenue">
        <div class="kpi-header"><span>Administrators</span><i class="bx bx-shield-quarter"></i></div>
        <div class="kpi-value">{{ $stats['total_admins'] }}</div>
        <div class="kpi-sub">Executive & system control</div>
    </div>
    <div class="kpi-card-custom kpi-lab">
        <div class="kpi-header"><span>Clinical Optometry</span><i class="bx bx-pulse"></i></div>
        <div class="kpi-value">{{ $stats['total_clinical'] }}</div>
        <div class="kpi-sub">Exam & prescription issuers</div>
    </div>
    <div class="kpi-card-custom kpi-noshow">
        <div class="kpi-header"><span>Optical Dispensing</span><i class="bx bx-glasses"></i></div>
        <div class="kpi-value">{{ $stats['total_dispensing'] }}</div>
        <div class="kpi-sub">POS & lab coordination</div>
    </div>
    <div class="kpi-card-custom kpi-inventory">
        <div class="kpi-header"><span>Front-Desk Queue</span><i class="bx bx-id-card"></i></div>
        <div class="kpi-value">{{ $stats['total_reception'] }}</div>
        <div class="kpi-sub">Check-in & receptionists</div>
    </div>
</div>

<!-- Staff Directory Section -->
<section class="admin-panel" style="margin-top: 0;">
    <div class="admin-section-head">
        <div>
            <h2>Authorized staff directory</h2>
            <p>Active medical professionals, opticians, reception desk coordinators, and clinic directors.</p>
        </div>
    </div>

    <div class="admin-record-list">
        @forelse($staffMembers as $staff)
        <article class="admin-patient" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px;">
            <div style="display: flex; align-items: center; gap: 14px; flex: 1.5;">
                <div class="patient-avatar" style="background: {{ $staff->isAdmin() ? '#9F652C' : '#64BCB4' }}; color: #fff;">
                    {{ strtoupper(substr($staff->name, 0, 1)) }}
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <h3 style="margin: 0; font-size: 16px;">{{ $staff->name }}</h3>
                        @if($staff->isOriginalAdmin())
                            <span class="staff-role-badge" style="background: rgba(240, 186, 127, 0.4); color: #7A5530; border: 1px solid var(--moa-gold);">
                                <i class="bx bxs-badge-check"></i> Owner / Admin
                            </span>
                        @else
                            <span class="staff-role-badge">
                                {{ ucfirst($staff->role) }}
                            </span>
                        @endif

                        @if($staff->id === $currentUser->id)
                            <span style="font-size: 10px; background: rgba(100, 188, 180, 0.15); color: #164e63; padding: 2px 6px; border-radius: 4px; font-weight: 700;">
                                (You)
                            </span>
                        @endif
                    </div>
                    <p style="margin: 3px 0 0; font-size: 13px; color: var(--moa-slate);">
                        {{ $staff->email }} · {{ $staff->phone ?: 'No direct phone' }}
                    </p>
                </div>
            </div>

            <!-- Management Actions -->
            <div style="display: flex; gap: 8px; align-items: center;">
                <!-- Edit Button (Opens Modal) -->
                <button type="button" class="button button-quiet" style="font-size: 12px; padding: 6px 14px;"
                    onclick="openEditModal({{ json_encode([
                        'id' => $staff->id,
                        'name' => $staff->name,
                        'email' => $staff->email,
                        'phone' => $staff->phone ?? '',
                        'role' => $staff->role,
                        'is_original' => $staff->isOriginalAdmin(),
                    ]) }})">
                    <i class="bx bx-edit-alt"></i> Edit Info
                </button>

                @if(! $staff->isOriginalAdmin() && $staff->id !== $currentUser->id)
                    <form method="POST" action="{{ route('admin.staff.revoke', $staff) }}" style="margin: 0;"
                        onsubmit="return confirm('Revoke staff privileges for {{ $staff->name }}? This will demote the account to Patient role.');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="button" style="background: rgba(224, 86, 76, 0.12); color: #C53030; border: 1px solid rgba(224, 86, 76, 0.3); font-size: 12px; padding: 6px 12px;">
                            <i class="bx bx-user-x"></i> Revoke
                        </button>
                    </form>
                @endif
            </div>
        </article>
        @empty
        <div class="admin-empty">No staff members found.</div>
        @endforelse
    </div>
</section>
@endsection

@push('modals')
<!-- 1. Add New Staff / Admin Modal -->
<div class="modal-backdrop" id="add-staff-modal" hidden>
    <section class="modal" role="dialog" aria-modal="true" style="max-width: 480px; width: 100%; border-radius: 12px; padding: 28px;">
        <button class="modal-close" type="button" onclick="document.getElementById('add-staff-modal').hidden = true;" aria-label="Close dialog">×</button>
        <div style="margin-bottom: 20px;">
            <h2 style="font-size: 22px; margin: 4px 0 0;">Add new staff or admin</h2>
            <p style="font-size: 13px; color: var(--moa-slate); margin: 4px 0 0;">Provision account credentials and allocate clinic authorization.</p>
        </div>

        <form method="POST" action="{{ route('admin.staff.store') }}" style="display: flex; flex-direction: column; gap: 14px;">
            @csrf
            <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 13px;">
                <span class="required">Full Name & Professional Title</span>
                <input type="text" name="name" placeholder="e.g. Dr. Jane Smith, OD" required style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
            </label>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 13px;">
                    <span class="required">Email Address</span>
                    <input type="email" name="email" placeholder="staff@moa.clinic" required style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
                </label>
                <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 13px;">
                    Phone Number
                    <input type="text" name="phone" placeholder="e.g. 555-0199" style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
                </label>
            </div>

            <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 13px;">
                <span class="required">Assigned Role & Privileges</span>
                <select name="role" required style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
                    <option value="admin">Administrator (Full System & User Control)</option>
                    <option value="optometrist">Optometrist (Clinical EHR, Examinations, Rx)</option>
                    <option value="doctor">Doctor (Consultations & Clinical Care)</option>
                    <option value="optician">Optician (Dispensing, Frame Inventory, Lab Orders)</option>
                    <option value="receptionist">Receptionist (Front-Desk, Queue, Appointments)</option>
                </select>
            </label>

            <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 13px;">
                <span class="required">Initial Password</span>
                <input type="password" name="password" placeholder="At least 8 characters" required minlength="8" style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
            </label>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 12px;">
                <button type="button" class="button button-quiet" onclick="document.getElementById('add-staff-modal').hidden = true;">
                    Cancel
                </button>
                <button type="submit" class="button button-teal">
                    <i class="bx bx-check"></i> Create Account
                </button>
            </div>
        </form>
    </section>
</div>

<!-- 2. Edit Staff Member Modal -->
<div class="modal-backdrop" id="edit-staff-modal" hidden>
    <section class="modal" role="dialog" aria-modal="true" style="max-width: 480px; width: 100%; border-radius: 12px; padding: 28px;">
        <button class="modal-close" type="button" onclick="document.getElementById('edit-staff-modal').hidden = true;" aria-label="Close dialog">×</button>
        <div style="margin-bottom: 20px;">
            <h2 style="font-size: 22px; margin: 4px 0 0;" id="edit-modal-title">Edit staff member</h2>
            <p style="font-size: 13px; color: var(--moa-slate); margin: 4px 0 0;">Update contact information, reassign role, or reset password.</p>
        </div>

        <form method="POST" id="edit-staff-form" action="" style="display: flex; flex-direction: column; gap: 14px;">
            @csrf
            @method('PATCH')

            <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 13px;">
                <span class="required">Full Name & Title</span>
                <input type="text" name="name" id="edit-name" required style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
            </label>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 13px;">
                    <span class="required">Email Address</span>
                    <input type="email" name="email" id="edit-email" required style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
                    <small id="original-admin-email-note" style="color: var(--moa-bronze); font-size: 11px; display: none;">* Original Admin email cannot be modified.</small>
                </label>
                <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 13px;">
                    Phone Number
                    <input type="text" name="phone" id="edit-phone" style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
                </label>
            </div>

            <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 13px;">
                <span class="required">Assigned Role</span>
                <select name="role" id="edit-role" required style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
                    <option value="admin">Administrator (Full System & User Control)</option>
                    <option value="optometrist">Optometrist (Clinical EHR, Examinations, Rx)</option>
                    <option value="doctor">Doctor (Consultations & Clinical Care)</option>
                    <option value="optician">Optician (Dispensing, Frame Inventory, Lab Orders)</option>
                    <option value="receptionist">Receptionist (Front-Desk, Queue, Appointments)</option>
                </select>
                <small id="original-admin-role-note" style="color: var(--moa-bronze); font-size: 11px; display: none;">* Original Admin cannot be demoted from the Administrator role.</small>
            </label>

            <div style="border-top: 1px dashed #D5CBC0; padding-top: 12px; margin-top: 4px;">
                <label style="display: flex; flex-direction: column; gap: 4px; font-weight: 600; font-size: 13px;">
                    Reset Password (Optional)
                    <input type="password" name="password" placeholder="Leave blank to keep current password" minlength="8" style="padding: 10px; border: 1px solid #D5CBC0; border-radius: 6px; font-size: 13px;">
                </label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 12px;">
                <button type="button" class="button button-quiet" onclick="document.getElementById('edit-staff-modal').hidden = true;">
                    Cancel
                </button>
                <button type="submit" class="button button-teal">
                    <i class="bx bx-check"></i> Save Changes
                </button>
            </div>
        </form>
    </section>
</div>
@endpush

@push('scripts')
<script>
function openEditModal(staff) {
    const form = document.getElementById('edit-staff-form');
    form.action = '/admin/staff/' + staff.id;

    document.getElementById('edit-name').value = staff.name;
    document.getElementById('edit-email').value = staff.email;
    document.getElementById('edit-phone').value = staff.phone || '';
    document.getElementById('edit-role').value = staff.role;

    const emailInput = document.getElementById('edit-email');
    const roleSelect = document.getElementById('edit-role');
    const emailNote = document.getElementById('original-admin-email-note');
    const roleNote = document.getElementById('original-admin-role-note');

    if (staff.is_original) {
        emailInput.readOnly = true;
        emailInput.style.backgroundColor = '#f5f0ea';
        emailNote.style.display = 'block';

        roleSelect.disabled = false; // keep enabled so it submits, or we can handle via hidden input
        roleSelect.querySelectorAll('option').forEach(opt => {
            opt.disabled = (opt.value !== 'admin');
        });
        roleNote.style.display = 'block';
    } else {
        emailInput.readOnly = false;
        emailInput.style.backgroundColor = '';
        emailNote.style.display = 'none';

        roleSelect.querySelectorAll('option').forEach(opt => {
            opt.disabled = false;
        });
        roleNote.style.display = 'none';
    }

    document.getElementById('edit-staff-modal').hidden = false;
}
</script>
@endpush

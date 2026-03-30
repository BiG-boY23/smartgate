@extends('layouts.app')

@section('title', 'User Role Management')
@section('subtitle', 'Manage system access for Office Staff, Guards, and Administrators.')

@section('content')
<div class="table-container">
    <div class="section-header">
        <h3>System User Accounts</h3>
        <button class="btn btn-primary" onclick="showAddUserModal()">
            <i class="ph ph-plus"></i> Add New User
        </button>
    </div>

    @if($errors->any())
    <div style="background: #fef2f2; color: #dc2626; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #fee2e2;">
        <ul style="margin: 0; padding-left: 1.2rem; font-size: 0.9rem;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(session('success'))
    <div style="background: #ecfdf5; color: #059669; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #d1fae5;">
        <i class="ph ph-check-circle"></i> {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div style="background: #fef2f2; color: #dc2626; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #fee2e2;">
        <i class="ph ph-warning-circle"></i> {{ session('error') }}
    </div>
    @endif

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Middle Name</th>
                    <th>Username</th>
                    <th>System Role</th>
                    <th>Created At</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td style="font-weight: 600;">{{ $user->first_name }}</td>
                    <td style="font-weight: 600;">{{ $user->last_name }}</td>
                    <td>{{ $user->middle_name ?: 'N/A' }}</td>
                    <td><span style="font-family: monospace; color: #64748b;">{{ $user->username }}</span></td>
                    <td>
                        @if($user->role === 'admin')
                            <span class="badge" style="background: #fee2e2; color: #dc2626;">Administrator</span>
                        @elseif($user->role === 'office')
                            <span class="badge" style="background: #e0e7ff; color: #4338ca;">Office Staff</span>
                        @else
                            <span class="badge" style="background: #fef3c7; color: #d97706;">Guard</span>
                        @endif
                    </td>
                    <td>{{ $user->created_at->format('M d, Y') }}</td>
                    <td style="text-align: right;">
                        <button class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" 
                                onclick="showEditUserModal({{ json_encode($user) }})">
                            <i class="ph ph-pencil-simple"></i> Edit
                        </button>
                        @if($user->id !== Auth::id())
                        <button type="button" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; color: #dc2626; border-color: #fee2e2;"
                                onclick="confirmDeleteUser({{ $user->id }}, '{{ $user->first_name }} {{ $user->last_name }}')">
                            <i class="ph ph-trash"></i> Delete
                        </button>
                        <form id="delete-user-{{ $user->id }}" action="{{ route('admin.users.delete', $user->id) }}" method="POST" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function togglePassword(id) {
        const input = document.getElementById(id);
        const icon = document.getElementById(id + '_icon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('ph-eye', 'ph-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('ph-eye-slash', 'ph-eye');
        }
    }

    function getPasswordPolicyHtml() {
        return `
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.75rem; color: #64748b;">
                <div style="font-weight: 600; margin-bottom: 0.4rem; color: #475569;">Password must contain:</div>
                <ul style="margin: 0; padding-left: 1.2rem; list-style-type: disc;">
                    <li>At least 8 characters</li>
                    <li>At least 1 uppercase letter</li>
                    <li>At least 1 lowercase letter</li>
                    <li>At least 1 number</li>
                    <li>At least 1 special character (@$!%*#?&)</li>
                </ul>
            </div>
        `;
    }

    function showAddUserModal() {
        Swal.fire({
            title: 'Add New System User',
            html: `
                <form id="addUserForm" action="{{ route('admin.users.store') }}" method="POST" style="text-align: left;">
                    @csrf
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div style="margin-bottom: 1rem;">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="swal2-input custom-swal-input" placeholder="e.g. John" required>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="swal2-input custom-swal-input" placeholder="e.g. Doe" required>
                        </div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Middle Name (Optional)</label>
                        <input type="text" name="middle_name" class="swal2-input custom-swal-input" placeholder="Optional">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="swal2-input custom-swal-input" placeholder="e.g. jdoe" required>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">System Role</label>
                        <select name="role" class="swal2-select custom-swal-input" style="border: 1px solid #d9d9d9; border-radius: 5px;" required>
                            <option value="office">Office Staff</option>
                            <option value="guard">Guard</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>

                    ${getPasswordPolicyHtml()}

                    <div style="margin-bottom: 1rem; position: relative;">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" id="add_pass" class="swal2-input custom-swal-input" required>
                        <i class="ph ph-eye password-toggle-icon" id="add_pass_icon" onclick="togglePassword('add_pass')"></i>
                    </div>
                    <div style="margin-bottom: 1rem; position: relative;">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="add_pass_conf" class="swal2-input custom-swal-input" required>
                        <i class="ph ph-eye password-toggle-icon" id="add_pass_conf_icon" onclick="togglePassword('add_pass_conf')"></i>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Create User',
            confirmButtonColor: '#1e293b',
            width: '550px',
            preConfirm: () => {
                const form = document.getElementById('addUserForm');
                const p1 = document.getElementById('add_pass').value;
                const p2 = document.getElementById('add_pass_conf').value;
                
                if (p1 !== p2) {
                    Swal.showValidationMessage('Passwords do not match');
                    return false;
                }
                
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return false;
                }
                form.submit();
            }
        });
    }

    function showEditUserModal(user) {
        Swal.fire({
            title: 'Edit System User',
            html: `
                <form id="editUserForm" action="{{ url('admin/users') }}/${user.id}" method="POST" style="text-align: left;">
                    @csrf
                    @method('PUT')
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div style="margin-bottom: 1rem;">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="swal2-input custom-swal-input" value="${user.first_name}" required>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="swal2-input custom-swal-input" value="${user.last_name}" required>
                        </div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="swal2-input custom-swal-input" value="${user.middle_name || ''}">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="swal2-input custom-swal-input" value="${user.username}" required>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">System Role</label>
                        <select name="role" class="swal2-select custom-swal-input" style="border: 1px solid #d9d9d9; border-radius: 5px;" required>
                            <option value="office" ${user.role === 'office' ? 'selected' : ''}>Office Staff</option>
                            <option value="guard" ${user.role === 'guard' ? 'selected' : ''}>Guard</option>
                            <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Administrator</option>
                        </select>
                    </div>

                    ${getPasswordPolicyHtml()}
                    <div style="font-size: 0.75rem; color: #f59e0b; margin-bottom: 0.5rem; font-weight: 500;">Leave blank to keep current password.</div>

                    <div style="margin-bottom: 1rem; position: relative;">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" id="edit_pass" class="swal2-input custom-swal-input">
                        <i class="ph ph-eye password-toggle-icon" id="edit_pass_icon" onclick="togglePassword('edit_pass')"></i>
                    </div>
                    <div style="margin-bottom: 1rem; position: relative;">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" id="edit_pass_conf" class="swal2-input custom-swal-input">
                        <i class="ph ph-eye password-toggle-icon" id="edit_pass_conf_icon" onclick="togglePassword('edit_pass_conf')"></i>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Update User',
            confirmButtonColor: '#1e293b',
            width: '550px',
            preConfirm: () => {
                const form = document.getElementById('editUserForm');
                const p1 = document.getElementById('edit_pass').value;
                const p2 = document.getElementById('edit_pass_conf').value;
                
                if (p1 && p1 !== p2) {
                    Swal.showValidationMessage('Passwords do not match');
                    return false;
                }
                
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return false;
                }
                form.submit();
            }
        });
    }

    function confirmDeleteUser(id, name) {
        Swal.fire({
            title: 'Delete User Account?',
            text: `Are you sure you want to remove ${name} from the system? This user will lose all access immediately.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#741b1b',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete account'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-user-' + id).submit();
            }
        });
    }
</script>

<style>
    .badge { padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
    .swal2-input { margin: 0 !important; }
    .custom-swal-input { 
        width: 100% !important; 
        margin: 0 !important; 
        height: 38px !important; 
        font-size: 0.9rem !important; 
    }
    .form-label {
        display: block; 
        font-size: 0.8rem; 
        font-weight: 600; 
        margin-bottom: 0.3rem;
        color: #475569;
    }
    .password-toggle-icon {
        position: absolute;
        right: 12px;
        bottom: 10px;
        cursor: pointer;
        color: #94a3b8;
        font-size: 1.25rem;
        transition: color 0.2s;
    }
    .password-toggle-icon:hover {
        color: #64748b;
    }
</style>
@endsection

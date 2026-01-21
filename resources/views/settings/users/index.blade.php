@extends('layouts.app')

@section('title', 'User Management - SyneriaBooks')

@section('content')
<div class="container-fluid py-4">
    <!-- Header: Title, Search, and Invite -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 text-dark fw-bold">User Management</h2>
            <p class="text-muted small mb-0">Manage access, roles, and approvals.</p>
        </div>
        
        <div class="d-flex gap-2">
            <!-- Search Form -->
            <form action="{{ route('settings.users.index') }}" method="GET" class="d-flex">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search users..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                    @if(request('search'))
                        <a href="{{ route('settings.users.index') }}" class="btn btn-outline-secondary" title="Clear Search">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>

            <!-- Invite Button -->
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#inviteUserModal">
                <i class="bi bi-person-plus me-1"></i> Invite User
            </button>
        </div>
    </div>

    <!-- Alerts -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Users Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive" style="min-height: 300px;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        {{ substr($user->first_name ?? $user->email, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $user->first_name ? $user->name : 'Pending Registration' }}</div>
                                        <div class="small text-muted">{{ $user->email }}</div>
                                        @if($user->position)
                                            <div class="small text-muted fst-italic">{{ $user->position }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php
                                    $badges = [
                                        'SuperAdministrator' => 'bg-danger',
                                        'Approver' => 'bg-success', 
                                        'Reviewer' => 'bg-warning text-dark',
                                        'Bookkeeper' => 'text-white', // Background handled via style
                                    ];
                                    $badgeClass = $badges[$user->role] ?? 'bg-secondary';
                                    $customStyle = $user->role === 'Bookkeeper' ? 'background-color: #8B4513;' : '';
                                @endphp
                                <span class="badge {{ $badgeClass }} text-capitalize" style="{{ $customStyle }}">{{ $user->role }}</span>
                            </td>
                            <td>
                                @if($user->status === 'active')
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @elseif($user->status === 'invited')
                                    <span class="badge bg-warning-subtle text-warning">Invited</span>
                                @elseif($user->status === 'pending')
                                    <span class="badge bg-warning-subtle text-warning">Needs Approval</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">Suspended</span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                            </td>
                            <td class="text-end pe-4">
                                @if($user->id !== Auth::id())
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <!-- Edit Access -->
                                            <li>
                                                <button type="button" class="dropdown-item edit-user-btn"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editUserModal"
                                                        data-user-id="{{ $user->id }}"
                                                        data-user-name="{{ $user->name ?? $user->email }}"
                                                        data-user-role="{{ $user->role }}"
                                                        data-update-url="{{ route('settings.users.update', $user->id) }}">
                                                    <i class="bi bi-gear me-2"></i>Edit Access
                                                </button>
                                            </li>
                                            
                                            <!-- Toggle Status -->
                                            @if($user->status !== 'invited')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('settings.users.toggle', $user->id) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="dropdown-item {{ $user->status === 'active' ? 'text-danger' : 'text-success' }}" onclick="return confirm('Are you sure you want to change this user\'s status?')">
                                                            @if($user->status === 'active')
                                                                <i class="bi bi-slash-circle me-2"></i>Suspend User
                                                            @else
                                                                <i class="bi bi-check-circle me-2"></i>Activate User
                                                            @endif
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                @else
                                    <span class="text-muted small">Current User</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-1 d-block mb-2"></i>
                                No users found matching your search.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        @if($users->hasPages())
        <div class="card-footer bg-white">
            {{ $users->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

    <!-- Invite User Modal -->
    <div class="modal fade" id="inviteUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('settings.users.invite') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Invite New User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Name Fields -->
                            <div class="col-md-6">
                                <label for="inviteFirstName" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="inviteFirstName" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="inviteLastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="inviteLastName" name="last_name" required>
                            </div>

                            <!-- Contact Info -->
                            <div class="col-12">
                                <label for="inviteEmail" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="inviteEmail" name="email" required placeholder="name@company.com">
                            </div>
                            <div class="col-md-6">
                                <label for="invitePhone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="invitePhone" name="phone">
                            </div>

                            <!-- Role & Position -->
                            <div class="col-md-6">
                                <label for="invitePosition" class="form-label">Position/Title</label>
                                <select class="form-select" id="invitePosition" name="position">
                                    <option value="" selected disabled>Select Position...</option>
                                    <option value="Owner / Proprietor">Owner / Proprietor</option>
                                    <option value="President / CEO">President / CEO</option>
                                    <option value="Chief Financial Officer (CFO)">Chief Financial Officer (CFO)</option>
                                    <option value="Finance Manager">Finance Manager</option>
                                    <option value="Accountant / Bookkeeper">Accountant / Bookkeeper</option>
                                    <option value="Administrative Officer">Administrative Officer</option>
                                    <option value="Sales Manager">Sales Manager</option>
                                    <option value="IT / System Admin">IT / System Admin</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="inviteRole" class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" id="inviteRole" class="form-select" required>
                                    <option value="" disabled selected>Select Role...</option>
                                    <option value="Bookkeeper">Bookkeeper (Maker)</option>
                                    <option value="Reviewer">Reviewer (Checker)</option>
                                    <option value="Approver">Approver (Signatory)</option>
                                    <option value="SuperAdministrator">SuperAdministrator</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-text mt-3">An invitation email will be sent to the user.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i> Send Invitation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Access Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editUserForm" method="POST" action="">
                    @csrf
                    @method('PUT')
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Set Access for <span id="modalUserName">User</span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Assign Role</label>
                            <select name="role" id="modalUserRole" class="form-select">
                                <option value="Bookkeeper">Bookkeeper (Maker) - Create Entries</option>
                                <option value="Reviewer">Reviewer (Checker) - Validate Entries</option>
                                <option value="Approver">Approver (Signatory) - Post Entries</option>
                                <option value="SuperAdministrator">SuperAdministrator - Full System Access</option>
                            </select>
                            <div class="form-text text-muted mt-2">
                                <strong>Note:</strong> 
                                Changing this role updates permissions immediately.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Edit Modal Logic
        var editButtons = document.querySelectorAll('.edit-user-btn');
        var modalUserName = document.getElementById('modalUserName');
        var modalUserRole = document.getElementById('modalUserRole');
        var editUserForm = document.getElementById('editUserForm');

        editButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                var name = this.getAttribute('data-user-name');
                var role = this.getAttribute('data-user-role');
                var url = this.getAttribute('data-update-url');

                modalUserName.textContent = name;
                modalUserRole.value = role;
                editUserForm.action = url;
            });
        });

        // Focus First Name input when Invite Modal opens
        var inviteModal = document.getElementById('inviteUserModal');
        var inviteInput = document.getElementById('inviteFirstName');

        inviteModal.addEventListener('shown.bs.modal', function () {
            inviteInput.focus();
        });
    });
</script>
@endsection
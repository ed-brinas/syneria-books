<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold">User Management</h2>
            <p class="text-muted small">Manage access, roles, and approvals for your organization.</p>
        </div>
        
        <!-- Invite Form -->
        <div class="d-flex gap-2">
            <input type="email" wire:model="inviteEmail" class="form-control" placeholder="Enter email to invite...">
            <button wire:click="inviteUser" class="btn btn-primary text-nowrap">
                <i class="bi bi-send me-1"></i> Invite User
            </button>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
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
                        @foreach($users as $user)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        {{ substr($user->first_name ?? $user->email, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $user->first_name ? $user->name : 'Pending Registration' }}</div>
                                        <div class="small text-muted">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php
                                    $badges = [
                                        'SuperAdministrator' => 'bg-dark',
                                        'Approver' => 'bg-purple-600', 
                                        'Reviewer' => 'bg-info text-dark',
                                        'Bookkeeper' => 'bg-secondary',
                                    ];
                                    $badge = $badges[$user->role] ?? 'bg-secondary';
                                @endphp
                                <span class="badge {{ $badge }} text-capitalize">{{ $user->role }}</span>
                            </td>
                            <td>
                                @if($user->status === 'active')
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Active</span>
                                @elseif($user->status === 'invited')
                                    <span class="badge bg-warning text-dark">Invited</span>
                                @elseif($user->status === 'pending')
                                    <span class="badge bg-warning text-dark">Needs Approval</span>
                                @else
                                    <span class="badge bg-danger">Suspended</span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                            </td>
                            <td class="text-end pe-4">
                                @if($user->id !== Auth::id())
                                    <button wire:click="manageUser('{{ $user->id }}')" class="btn btn-sm btn-outline-primary me-1">
                                        <i class="bi bi-gear"></i> Edit Access
                                    </button>
                                    
                                    @if($user->status !== 'invited')
                                        <button wire:click="toggleStatus('{{ $user->id }}')" class="btn btn-sm {{ $user->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                            <i class="bi bi-power"></i>
                                        </button>
                                    @endif
                                @else
                                    <span class="text-muted small">Current User</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Edit/Approve Modal Overlay -->
    @if($selectedUser)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Set Access for {{ $selectedUser->name ?? $selectedUser->email }}</h5>
                    <button wire:click="$set('selectedUser', null)" type="button" class="btn-close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Assign Role</label>
                        <select wire:model="editingRole" class="form-select">
                            <option value="Bookkeeper">Bookkeeper (Maker) - Create Entries</option>
                            <option value="Reviewer">Reviewer (Checker) - Validate Entries</option>
                            <option value="Approver">Approver (Signatory) - Post Entries</option>
                            <option value="SuperAdministrator">SuperAdministrator - Full System Access</option>
                        </select>
                        <div class="form-text text-muted mt-2">
                            <strong>Note:</strong> 
                            @if($editingRole === 'Bookkeeper')
                                Can draft journals but cannot approve or post them.
                            @elseif($editingRole === 'Reviewer')
                                Can review drafts but cannot post to GL.
                            @elseif($editingRole === 'Approver')
                                Can post reviewed journals to the General Ledger.
                            @elseif($editingRole === 'SuperAdministrator')
                                Full access to settings and user management.
                            @endif
                        </div>
                    </div>
                    
                    @if($selectedUser->status === 'invited' || $selectedUser->status === 'pending')
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-1"></i> Saving this will officially <strong>Approve</strong> the user and set them to Active.
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button wire:click="$set('selectedUser', null)" type="button" class="btn btn-secondary">Cancel</button>
                    <button wire:click="saveAccess" type="button" class="btn btn-primary">Save & Approve</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
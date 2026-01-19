@extends('layouts.app')

@section('title', 'Chart of Accounts - SyneriaBooks')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 text-dark fw-bold">Chart of Accounts</h2>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createAccountModal">
            <i class="bi bi-plus-lg"></i> New Account
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                        <tr class="{{ !$account->is_active ? 'bg-light text-muted' : '' }}">
                            <td class="ps-4 fw-bold">{{ $account->code }}</td>
                            <td>{{ $account->name }}</td>
                            <td>
                                <span class="badge bg-secondary text-uppercase" style="font-size: 0.7rem;">
                                    {{ $account->type }}
                                </span>
                            </td>
                            <td class="small {{ !$account->is_active ? 'text-muted' : '' }}">{{ Str::limit($account->description, 40) }}</td>
                            <td class="text-center">
                                @if($account->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                @if($account->is_system)
                                    <span class="text-muted" title="System Account (Locked)">
                                        <i class="bi bi-lock-fill"></i>
                                    </span>
                                @else
                                    <div class="btn-group">
                                        {{-- Edit Button: Pass the route URL explicitly --}}
                                        <button type="button" 
                                            class="btn btn-sm btn-outline-primary"
                                            onclick="editAccount({{ $account }}, '{{ route('accounts.update', $account->id) }}')"
                                            title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        
                                        {{-- Delete Button --}}
                                        @if($account->is_active)
                                            <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteModal{{ $account->id }}"
                                                title="Deactivate">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </div>

                                    {{-- Delete Confirmation Modal --}}
                                    @if($account->is_active)
                                    <div class="modal fade text-start" id="deleteModal{{ $account->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title text-danger">Deactivate Account</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to deactivate <strong>{{ $account->name }}</strong>?</p>
                                                    <p class="small text-muted">This will prevent it from being selected in future transactions, but existing history remains.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <form action="{{ route('accounts.destroy', $account->id) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Deactivate</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No accounts found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Create Account Modal --}}
<div class="modal fade" id="createAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('accounts.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Account Type</label>
                        <select name="type" class="form-select" required>
                            <option value="">Select Type</option>
                            <option value="asset">Asset</option>
                            <option value="liability">Liability</option>
                            <option value="equity">Equity</option>
                            <option value="revenue">Revenue</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">GL Code</label>
                        <input type="text" name="code" class="form-control" placeholder="e.g. 1000" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Cash in Bank" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subtype (Optional)</label>
                        <input type="text" name="subtype" class="form-control" placeholder="e.g. Cash, Payable, Operating">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Account</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Edit Account Modal --}}
<div class="modal fade" id="editAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Account Type</label>
                        <select name="type" id="edit_type" class="form-select" required>
                            <option value="asset">Asset</option>
                            <option value="liability">Liability</option>
                            <option value="equity">Equity</option>
                            <option value="revenue">Revenue</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">GL Code</label>
                        <input type="text" name="code" id="edit_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subtype (Optional)</label>
                        <input type="text" name="subtype" id="edit_subtype" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                        <label class="form-check-label" for="edit_is_active">Active Status</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Account</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Updated function to accept URL directly from Blade
    function editAccount(account, updateUrl) {
        // Set form action using the generated URL
        document.getElementById('editForm').action = updateUrl;
        
        // Populate fields
        document.getElementById('edit_type').value = account.type.toLowerCase();
        document.getElementById('edit_code').value = account.code;
        document.getElementById('edit_name').value = account.name;
        document.getElementById('edit_subtype').value = account.subtype || '';
        document.getElementById('edit_description').value = account.description || '';
        
        // Handle Active Checkbox
        document.getElementById('edit_is_active').checked = account.is_active;

        // Show Modal
        new bootstrap.Modal(document.getElementById('editAccountModal')).show();
    }
</script>
@endsection
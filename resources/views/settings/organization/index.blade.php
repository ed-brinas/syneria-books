@extends('layouts.app')

@section('title', 'Organization Settings - SyneriaBooks')

@section('content')

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 text-dark fw-bold mb-0">Organization Settings</h2>
            <p class="text-muted small mb-0">Manage your company details, branding, and financial information.</p>
        </div>
        <a href="#" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-eye me-1"></i> View Public Profile
        </a>
    </div>

    <!-- Feedback Messages -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-octagon-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Global Error Alert --}}
    @if($errors->any())
        <div class="alert alert-danger shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> Please check the form for errors.
        </div>
    @endif

    <div class="row g-4">
        <!-- LEFT COLUMN: Main Settings forms -->
        <div class="col-lg-7">
            
            <!-- Details & Contact Info -->
            <form action="{{ route('settings.organization.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold">Company Profile</h6>
                    </div>
                    <div class="card-body">
                        <!-- Logo Upload (Fixed with fallback) -->
                        <div class="d-flex align-items-center mb-4">
                            <div class="me-3 position-relative">
                                <img src="{{ $tenant->logo_url }}" 
                                     alt="Logo" 
                                     class="rounded-circle border" 
                                     width="80" height="80" 
                                     style="object-fit: cover;"
                                     onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($tenant->company_name) }}&color=7F9CF5&background=EBF4FF';">
                            </div>
                            <div>
                                <label for="logo" class="form-label small fw-bold mb-1">Company Logo</label>
                                <input type="file" name="logo" class="form-control form-control-sm @error('logo') is-invalid @enderror" accept="image/*">
                                <div class="form-text small">Max 2MB. PNG, JPG.</div>
                            </div>
                        </div>

                        <!-- Basic Info -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Company Name <span class="text-danger">*</span></label>
                                <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name', $tenant->company_name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Trade Name</label>
                                <input type="text" name="trade_name" class="form-control" value="{{ old('trade_name', $tenant->trade_name) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small text-muted">Business Type</label>
                                <select name="business_type" class="form-select" id="business_type_select">
                                    <option value="">Select Type</option>
                                    @foreach($businessTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('business_type', $tenant->business_type) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Country</label>
                                <select name="country" class="form-select @error('country') is-invalid @enderror" id="country_select">
                                    @foreach($countries as $code => $name)
                                        <option value="{{ $code }}" {{ old('country', $tenant->country) == $code ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Registration Details -->
                            <div class="col-md-6">
                                <label class="form-label small text-muted">TIN (Tax ID)</label>
                                <input type="text" name="tax_identification_number" class="form-control" value="{{ old('tax_identification_number', $tenant->tax_identification_number) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Registration / SEC No.</label>
                                <input type="text" name="company_reg_number" class="form-control" value="{{ old('company_reg_number', $tenant->company_reg_number) }}">
                            </div>

                            <!-- Address -->
                            <div class="col-12">
                                <label class="form-label small text-muted">Registered Address <span class="text-danger">*</span></label>
                                <textarea name="business_address" class="form-control @error('business_address') is-invalid @enderror" rows="2" required>{{ old('business_address', $tenant->business_address) }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small text-muted">City</label>
                                <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $tenant->city) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Postal Code</label>
                                <input type="text" name="postal_code" class="form-control @error('postal_code') is-invalid @enderror" value="{{ old('postal_code', $tenant->postal_code) }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white text-end py-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- RIGHT COLUMN: Branches & Banking -->
        <div class="col-lg-5">

            <!-- BRANCH MANAGEMENT (New) -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Branches & Locations</h6>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addBranchModal">
                        <i class="bi bi-plus-lg"></i> Add Branch
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">Code</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($branches as $branch)
                                <tr>
                                    <td class="ps-3 fw-bold font-monospace text-muted">{{ $branch->code }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $branch->name }}</div>
                                        @if($branch->is_default)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill" style="font-size: 0.65rem;">Head Office</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($branch->is_active ?? true)
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-link text-muted p-0" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <button class="dropdown-item small" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editBranchModal"
                                                        data-branch-id="{{ $branch->id }}"
                                                        data-branch-name="{{ $branch->name }}"
                                                        data-branch-code="{{ $branch->code }}"
                                                        data-branch-tin="{{ $branch->tin }}"
                                                        data-branch-rdo="{{ $branch->rdo_code ?? '' }}"
                                                        data-branch-address="{{ $branch->address }}"
                                                        data-branch-city="{{ $branch->city }}"
                                                        data-branch-zip="{{ $branch->zip_code }}"
                                                        data-branch-phone="{{ $branch->phone ?? '' }}"
                                                        data-branch-email="{{ $branch->email ?? '' }}"
                                                        data-branch-active="{{ $branch->is_active ?? 1 }}"
                                                        data-branch-default="{{ $branch->is_default }}"
                                                        data-update-url="{{ route('settings.organization.branch.update', $branch->id) }}">
                                                        <i class="bi bi-pencil me-2"></i> Edit Details
                                                    </button>
                                                </li>
                                                @if(!$branch->is_default)
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button class="dropdown-item small text-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteBranchModal"
                                                        data-branch-id="{{ $branch->id }}"
                                                        data-branch-name="{{ $branch->name }}"
                                                        data-delete-url="{{ route('settings.organization.branch.destroy', $branch->id) }}">
                                                        <i class="bi bi-archive me-2"></i> Deactivate
                                                    </button>
                                                </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">No branches found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- BANK ACCOUNTS -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Bank Accounts</h6>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addBankModal">
                        <i class="bi bi-plus-lg"></i> Add Account
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($tenant->bankAccounts as $bank)
                        <div class="list-group-item d-flex justify-content-between align-items-center px-3 py-3">
                            <div>
                                <div class="fw-bold text-dark">{{ $bank->bank_name }}</div>
                                <div class="text-muted small font-monospace">
                                    {{ $bank->currency }} • {{ Str::mask($bank->account_number, '*', -4) }}
                                </div>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link text-muted p-0" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li>
                                        <button class="dropdown-item small" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editBankModal"
                                            data-bank-id="{{ $bank->id }}"
                                            data-bank-name="{{ $bank->bank_name }}"
                                            data-account-name="{{ $bank->account_name }}"
                                            data-account-number="{{ $bank->account_number }}"
                                            data-currency="{{ $bank->currency }}"
                                            data-branch-code="{{ $bank->branch_code }}"
                                            data-swift-code="{{ $bank->swift_code }}"
                                            data-address="{{ $bank->address }}"
                                            data-update-url="{{ route('settings.organization.bank.update', $bank->id) }}">
                                            <i class="bi bi-pencil me-2"></i> Edit Details
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button class="dropdown-item small text-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteBankModal"
                                            data-bank-name="{{ $bank->bank_name }}"
                                            data-delete-url="{{ route('settings.organization.bank.destroy', $bank->id) }}">
                                            <i class="bi bi-trash me-2"></i> Deactivate
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        @empty
                        <div class="p-4 text-center text-muted small">
                            No bank accounts linked yet.
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ================= MODALS ================= -->

{{-- ADD BRANCH MODAL --}}
<div class="modal fade" id="addBranchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('settings.organization.branch.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add New Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small text-muted">Branch Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Cebu Branch" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Branch Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" 
                                placeholder="{{ $tenant->country === 'PH' ? 'e.g. 001' : 'e.g. HQ-01' }}" 
                                maxlength="{{ $tenant->country === 'PH' ? '5' : '10' }}" 
                                required>
                            <div class="form-text small">
                                @if($tenant->country === 'PH')
                                    BIR Standard: 3-5 digits (e.g., 000 for HO).
                                @else
                                    Alphanumeric allowed.
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small text-muted">TIN (9-digit + suffix)</label>
                            <input type="text" name="tin" class="form-control" placeholder="000-000-000-00001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">RDO Code</label>
                            <input type="text" name="rdo_code" class="form-control" placeholder="e.g. 050">
                        </div>

                        <div class="col-12">
                            <label class="form-label small text-muted">Address <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">City</label>
                            <input type="text" name="city" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Zip Code</label>
                            <input type="text" name="zip_code" class="form-control">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Contact Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Contact Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Branch</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- EDIT BRANCH MODAL --}}
<div class="modal fade" id="editBranchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="" method="POST" id="editBranchForm">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small text-muted">Branch Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_branch_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Branch Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="edit_branch_code" class="form-control" 
                                maxlength="{{ $tenant->country === 'PH' ? '5' : '10' }}" 
                                required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small text-muted">TIN</label>
                            <input type="text" name="tin" id="edit_branch_tin" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">RDO Code</label>
                            <input type="text" name="rdo_code" id="edit_branch_rdo" class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="form-label small text-muted">Address</label>
                            <input type="text" name="address" id="edit_branch_address" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">City</label>
                            <input type="text" name="city" id="edit_branch_city" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Zip Code</label>
                            <input type="text" name="zip_code" id="edit_branch_zip" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small text-muted">Phone</label>
                            <input type="text" name="phone" id="edit_branch_phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Email</label>
                            <input type="email" name="email" id="edit_branch_email" class="form-control">
                        </div>
                        
                        <div class="col-12 border-top pt-3 mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_branch_active">
                                <label class="form-check-label" for="edit_branch_active">Branch is Active</label>
                            </div>
                            <div class="form-check mt-2" id="default_branch_option">
                                <input class="form-check-input" type="checkbox" name="set_default" value="1" id="edit_branch_default">
                                <label class="form-check-label text-primary" for="edit_branch_default">Set as Head Office (Default)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- DELETE/DEACTIVATE BRANCH MODAL --}}
<div class="modal fade" id="deleteBranchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" id="deleteBranchForm">
            @csrf
            @method('DELETE')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger fw-bold">Deactivate Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to deactivate <strong id="delete_branch_name"></strong>?</p>
                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <strong>Compliance Notice:</strong> <br>
                        This branch will be hidden from new transactions, but historical records will be preserved permanently for tax audit purposes.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Deactivation</button>
                </div>
            </div>
        </form>
    </div>
</div>


{{-- ADD BANK MODAL --}}
<div class="modal fade" id="addBankModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('settings.organization.bank.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Bank Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small text-muted">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control" placeholder="e.g. BDO, BPI" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Account Name</label>
                            <input type="text" name="account_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Currency</label>
                            <select name="currency" class="form-select">
                                @foreach($currencies as $code => $name)
                                    <option value="{{ $code }}" {{ $code == 'PHP' ? 'selected' : '' }}>{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted">Account Number</label>
                            <input type="text" name="account_number" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Swift Code</label>
                            <input type="text" name="swift_code" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">BRSTN / Branch Code</label>
                            <input type="text" name="branch_code" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted">Bank Address</label>
                            <input type="text" name="address" class="form-control">
                        </div>
                        
                        {{-- Auto-generate COA Warning --}}
                        <div class="col-12 mt-3">
                            <div class="alert alert-info py-2 small mb-0">
                                <i class="bi bi-info-circle me-1"></i> A linked Chart of Accounts ledger will be automatically created.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Account</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- EDIT BANK MODAL --}}
<div class="modal fade" id="editBankModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" id="editBankForm">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Bank Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small text-muted">Bank Name</label>
                            <input type="text" name="bank_name" id="edit_bank_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Account Name</label>
                            <input type="text" name="account_name" id="edit_account_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Currency</label>
                            <input type="text" name="currency" id="edit_currency" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted">Account Number</label>
                            <input type="text" name="account_number" id="edit_account_number" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small text-muted">Swift Code</label>
                            <input type="text" name="swift_code" id="edit_swift_code" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">BRSTN / Branch Code</label>
                            <input type="text" name="branch_code" id="edit_bank_branch_code" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted">Bank Address</label>
                            <input type="text" name="address" id="edit_address" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Account</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- DELETE/DEACTIVATE BANK MODAL --}}
<div class="modal fade" id="deleteBankModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" id="deleteBankForm">
            @csrf
            @method('DELETE')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger fw-bold">Remove Bank Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to deactivate <strong id="delete_bank_name"></strong>?</p>
                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <strong>Compliance Notice:</strong> <br>
                        This account will be hidden from new transactions, but historical records will be preserved permanently for audit purposes.
                    </div>                    
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Removal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- Populate Edit Branch Modal ---
        var editBranchModal = document.getElementById('editBranchModal');
        editBranchModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            
            // Map data attributes to inputs
            document.getElementById('editBranchForm').action = button.getAttribute('data-update-url');
            document.getElementById('edit_branch_name').value = button.getAttribute('data-branch-name');
            document.getElementById('edit_branch_code').value = button.getAttribute('data-branch-code');
            document.getElementById('edit_branch_tin').value = button.getAttribute('data-branch-tin');
            document.getElementById('edit_branch_rdo').value = button.getAttribute('data-branch-rdo');
            document.getElementById('edit_branch_address').value = button.getAttribute('data-branch-address');
            document.getElementById('edit_branch_city').value = button.getAttribute('data-branch-city');
            document.getElementById('edit_branch_zip').value = button.getAttribute('data-branch-zip');
            document.getElementById('edit_branch_phone').value = button.getAttribute('data-branch-phone');
            document.getElementById('edit_branch_email').value = button.getAttribute('data-branch-email');
            
            // Checkboxes
            document.getElementById('edit_branch_active').checked = button.getAttribute('data-branch-active') == '1';
            
            // Handle Default Branch Logic (Cannot unset default from here, only set new one)
            var isDefault = button.getAttribute('data-branch-default') == '1';
            document.getElementById('edit_branch_default').checked = isDefault;
            document.getElementById('edit_branch_default').disabled = isDefault; // Cannot untoggle, must set another branch
            
            if(isDefault) {
                document.getElementById('edit_branch_active').disabled = true; // Cannot deactivate Head Office
            } else {
                 document.getElementById('edit_branch_active').disabled = false;
            }
        });

        // --- Populate Delete Branch Modal ---
        var deleteBranchModal = document.getElementById('deleteBranchModal');
        deleteBranchModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('deleteBranchForm').action = button.getAttribute('data-delete-url');
            document.getElementById('delete_branch_name').textContent = button.getAttribute('data-branch-name');
        });

        // --- Populate Edit Bank Modal ---
        var editBankModal = document.getElementById('editBankModal');
        editBankModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('editBankForm').action = button.getAttribute('data-update-url');
            document.getElementById('edit_bank_name').value = button.getAttribute('data-bank-name');
            document.getElementById('edit_account_name').value = button.getAttribute('data-account-name');
            document.getElementById('edit_account_number').value = button.getAttribute('data-account-number');
            document.getElementById('edit_currency').value = button.getAttribute('data-currency');
            
            // Populate new fields
            document.getElementById('edit_swift_code').value = button.getAttribute('data-swift-code');
            document.getElementById('edit_bank_branch_code').value = button.getAttribute('data-branch-code');
            document.getElementById('edit_address').value = button.getAttribute('data-address');
        });

        // --- Populate Delete Bank Modal ---
        var deleteBankModal = document.getElementById('deleteBankModal');
        deleteBankModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('deleteBankForm').action = button.getAttribute('data-delete-url');
            document.getElementById('delete_bank_name').textContent = button.getAttribute('data-bank-name');
        });

        // --- Dynamic Country/Business Type Logic (Optional visual refinement) ---
        const countrySelect = document.getElementById('country_select');
        const businessSelect = document.getElementById('business_type_select');
        
        // Logic to swap business types could go here if full arrays were available in JS
        // For now handled by Controller passing correct initial list.
    });
</script>

@endsection
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

    @if($errors->any())
        <div class="alert alert-danger shadow-sm">
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <!-- LEFT COLUMN: Main Settings forms -->
        <div class="col-lg-8">
            
            <!-- UNIFIED FORM: Details & Contact Info -->
            <form action="{{ route('settings.organization.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-building-gear me-2 text-primary"></i>Organization Profile
                        </h6>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- SECTION 1: Basic Details -->
                        <h6 class="fw-bold text-secondary text-uppercase small mb-3">Basic Details</h6>
                        <div class="row g-3 mb-4">
                            <!-- Display Name (Company Name) -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Display Name <span class="text-danger">*</span></label>
                                <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $tenant->company_name) }}" required>
                                <div class="form-text">The name displayed on your dashboard and invoices.</div>
                            </div>

                            <!-- Legal / Trading Name -->
                            <div class="col-md-6">
                                <label class="form-label">Legal / Trading Name</label>
                                <input type="text" name="trade_name" class="form-control" value="{{ old('trade_name', $tenant->trade_name) }}">
                            </div>

                            <!-- Organization Type -->
                            <div class="col-md-6">
                                <label class="form-label">Organization Type</label>
                                <select name="business_type" class="form-select">
                                    <option value="" disabled {{ !$tenant->business_type ? 'selected' : '' }}>Select type...</option>
                                    <option value="Sole Trader" {{ $tenant->business_type == 'Sole Trader' ? 'selected' : '' }}>Sole Trader</option>
                                    <option value="Partnership" {{ $tenant->business_type == 'Partnership' ? 'selected' : '' }}>Partnership</option>
                                    <option value="Company" {{ $tenant->business_type == 'Company' ? 'selected' : '' }}>Company / Corporation</option>
                                    <option value="Non-Profit" {{ $tenant->business_type == 'Non-Profit' ? 'selected' : '' }}>Non-Profit / Charity</option>
                                </select>
                            </div>

                            <!-- Registration Number -->
                            <div class="col-md-6">
                                <label class="form-label">Registration Number</label>
                                <input type="text" name="company_reg_number" class="form-control" value="{{ old('company_reg_number', $tenant->company_reg_number) }}" placeholder="e.g. SEC/DTI Reg No.">
                            </div>

                            <!-- Tax ID -->
                            <div class="col-md-6">
                                <label class="form-label">Tax ID / TIN</label>
                                <input type="text" name="tax_identification_number" class="form-control" value="{{ old('tax_identification_number', $tenant->tax_identification_number) }}" placeholder="000-000-000-000">
                            </div>
                        </div>

                        <hr class="text-muted opacity-25 my-4">

                        <!-- SECTION 2: Contact Information -->
                        <h6 class="fw-bold text-secondary text-uppercase small mb-3">Contact Information</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Postal / Physical Address</label>
                                <textarea name="business_address" class="form-control" rows="2">{{ old('business_address', $tenant->business_address) }}</textarea>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" value="{{ old('city', $tenant->city) }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="postal_code" class="form-control" value="{{ old('postal_code', $tenant->postal_code) }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Country</label>
                                <select name="country" class="form-select">
                                    <option value="" disabled>Select Country...</option>
                                    @foreach($countries as $code => $name)
                                        <option value="{{ $code }}" {{ old('country', $tenant->country) == $code ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Single Submit Button for the Unified Form -->
                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- SECTION 3: Financial & Banking -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-bank me-2 text-primary"></i>Bank Accounts</h6>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addBankModal">
                        <i class="bi bi-plus-lg me-1"></i> Add Bank Account
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4">Bank Name</th>
                                    <th>Account Name</th>
                                    <th>Account Number</th>
                                    <th>Currency</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($tenant->bankAccounts) && count($tenant->bankAccounts) > 0)
                                    @foreach($tenant->bankAccounts as $bank)
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark">{{ $bank->bank_name }}</td>
                                            <td>{{ $bank->account_name }}</td>
                                            <td class="font-monospace">{{ $bank->account_number }}</td>
                                            <td><span class="badge bg-light text-dark border">{{ $bank->currency }}</span></td>
                                            <td class="text-end pe-4">
                                                <form action="{{ route('settings.organization.bank.destroy', $bank->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this bank account?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-danger p-0" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <div class="py-3">
                                                <i class="bi bi-credit-card-2-front display-6 d-block mb-2 text-secondary opacity-50"></i>
                                                No bank accounts added yet.
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: Branding & System Info -->
        <div class="col-lg-4">
            
            <!-- Branding / Logo Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark">Branding</h6>
                </div>
                <div class="card-body p-4 text-center">
                    <form action="{{ route('settings.organization.logo') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-3 position-relative d-inline-block">
                            <!-- Use the accessor directly -->
                            <img src="{{ $tenant->logo_url }}" class="img-fluid rounded border p-1" style="max-height: 150px; width: auto;" alt="Organization Logo">
                        </div>

                        <div class="small text-muted mb-3">
                            Upload your organization logo.<br>
                            Recommended size: 400x400px (JPG/PNG).
                        </div>

                        <div class="input-group input-group-sm">
                            <input type="file" name="logo" class="form-control" accept="image/png, image/jpeg">
                            <button class="btn btn-outline-secondary" type="submit">Upload</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Subscription / System Info Card -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark">Subscription Details</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="small text-muted text-uppercase fw-bold">Domain</label>
                        <div class="d-flex align-items-center">
                            <span class="fs-5 fw-bold text-dark">{{ $tenant->domain ?? 'Not set' }}</span>
                            <span class="text-muted ms-1">.syneriabooks.com</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="small text-muted text-uppercase fw-bold">Current Plan</label>
                        <div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                {{ ucfirst($tenant->subscription_plan) }} Plan
                            </span>
                        </div>
                    </div>

                    @if($tenant->subscription_expires_at)
                    <div class="mb-0">
                        <label class="small text-muted text-uppercase fw-bold">Expires On</label>
                        <div class="text-dark">{{ $tenant->subscription_expires_at->format('M d, Y') }}</div>
                    </div>
                    @endif
                    
                    <hr>
                    <a href="#" class="btn btn-sm btn-link text-decoration-none p-0">Manage Subscription &rarr;</a>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal: Add Bank Account -->
<div class="modal fade" id="addBankModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('settings.organization.bank.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Bank Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                        <input type="text" name="bank_name" class="form-control" placeholder="e.g. BDO, BPI" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Name <span class="text-danger">*</span></label>
                        <input type="text" name="account_name" class="form-control" placeholder="Account Holder Name" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label">Account Number <span class="text-danger">*</span></label>
                            <input type="text" name="account_number" class="form-control" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Currency</label>
                            <select name="currency" class="form-select">
                            @foreach($currencies as $code => $name)
                                <option value="{{ $code }}" {{ (old('currency_code', $bank->currency_code ?? 'USD') == $code) ? 'selected' : '' }}>
                                    {{ $code }} - {{ $name }}
                                </option>
                            @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="alert alert-info py-2 small">
                        <i class="bi bi-info-circle me-1"></i> These details will appear on your invoices.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Account</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
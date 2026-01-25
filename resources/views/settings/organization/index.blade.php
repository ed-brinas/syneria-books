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

    {{-- Global Error Alert --}}
    @if($errors->any())
        <div class="alert alert-danger shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> Please check the form for errors.
        </div>
    @endif

    <div class="row g-4">
        <!-- LEFT COLUMN: Main Settings forms -->
        <div class="col-lg-8">
            
            <!-- Details & Contact Info -->
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

                            <!-- Organization Type (Dynamic ID added) -->
                            <div class="col-md-6">
                                <label class="form-label">Organization Type</label>
                                <select name="business_type" id="business_type_select" class="form-select">
                                    <option value="" disabled selected>Select type...</option>
                                    {{-- Options will be populated by JS based on country --}}
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

                            <!-- Country (Dynamic ID added) -->
                            <div class="col-md-4">
                                <label class="form-label">Country</label>
                                <select name="country" id="country_select" class="form-select">
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
                                                 <!-- Edit Button triggering Modal -->
                                                 <button type="button" 
                                                        class="btn btn-link text-primary p-0 me-2" 
                                                        title="Edit Bank" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editBankModal"
                                                        data-update-url="{{ route('settings.organization.bank.update', $bank->id) }}"
                                                        data-bank-id="{{ $bank->id }}"
                                                        data-bank-name="{{ $bank->bank_name }}"
                                                        data-account-name="{{ $bank->account_name }}"
                                                        data-account-number="{{ $bank->account_number }}"
                                                        data-currency="{{ $bank->currency }}"
                                                        data-branch-code="{{ $bank->branch_code }}"
                                                        data-swift-code="{{ $bank->swift_code }}"
                                                        data-address="{{ $bank->address }}">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>

                                                <!-- Delete Button triggering Modal -->
                                                <button type="button" 
                                                        class="btn btn-link text-danger p-0" 
                                                        title="Remove / Deactivate" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteBankModal"
                                                        data-action="{{ route('settings.organization.bank.destroy', $bank->id) }}"
                                                        data-bank-name="{{ $bank->bank_name }}"
                                                        data-account-number="{{ $bank->account_number }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
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
                            <input type="file" name="photo" class="form-control" accept="image/png, image/jpeg">
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
                        <label class="small text-muted text-uppercase fw-bold">Current Plan</label>
                        <div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                {{ ucfirst($tenant->subscription_plan) }} Plan
                            </span>
                        </div>
                    </div>

                    @if($tenant->subscription_expires_at && $tenant->subscription_plan!='free')
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
    <div class="modal-dialog modal-lg">
        <form action="{{ route('settings.organization.bank.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Bank Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    
                    <!-- Section: Bank Details -->
                    <h6 class="fw-bold text-secondary text-uppercase small mb-3">Bank Details</h6>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                            <input type="text" name="bank_name" class="form-control @error('bank_name', 'addBank') is-invalid @enderror" value="{{ old('bank_name') }}" placeholder="e.g. BDO, BPI" required>
                            @error('bank_name', 'addBank')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Holder <span class="text-danger">*</span></label>
                            <input type="text" name="account_name" class="form-control @error('account_name', 'addBank') is-invalid @enderror" value="{{ old('account_name') }}" placeholder="Company Name" required>
                            @error('account_name', 'addBank')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Number <span class="text-danger">*</span></label>
                            <input type="text" name="account_number" class="form-control @error('account_number', 'addBank') is-invalid @enderror" value="{{ old('account_number') }}" required>
                             @error('account_number', 'addBank')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Currency <span class="text-danger">*</span></label>
                            <select name="currency" class="form-select @error('currency', 'addBank') is-invalid @enderror">
                                <option value="" >Select one...</option>
                                @foreach($currencies as $code => $name)
                                    <option value="{{ $code }}" {{ (old('currency') == $code) ? 'selected' : '' }}>
                                        {{ $code }} - {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                             @error('currency', 'addBank')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch Code</label>
                            <input type="text" name="branch_code" class="form-control @error('branch_code', 'addBank') is-invalid @enderror" value="{{ old('branch_code') }}">
                             @error('branch_code', 'addBank')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Swift Code</label>
                            <input type="text" name="swift_code" class="form-control @error('swift_code', 'addBank') is-invalid @enderror" value="{{ old('swift_code') }}">
                             @error('swift_code', 'addBank')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bank Address</label>
                            <textarea name="address" class="form-control @error('address', 'addBank') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                             @error('address', 'addBank')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div> 
                    </div>

                    <hr class="my-4">

                    <!-- Section: COA Mapping -->
                    <h6 class="fw-bold text-secondary text-uppercase small mb-3">Chart of Accounts Mapping</h6>
                    <div class="alert alert-light border small text-muted mb-3">
                        <i class="bi bi-diagram-3 me-1"></i> A corresponding account will be created in your General Ledger.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">GL Code <span class="text-danger">*</span></label>
                            <input type="text" name="coa_code" class="form-control @error('coa_code', 'addBank') is-invalid @enderror" value="{{ old('coa_code') }}" placeholder="e.g. 1010" required>
                             @error('coa_code', 'addBank')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">GL Account Name <span class="text-danger">*</span></label>
                            <input type="text" name="coa_name" class="form-control @error('coa_name', 'addBank') is-invalid @enderror" value="{{ old('coa_name') }}" placeholder="e.g. Cash in Bank - BDO" required>
                             @error('coa_name', 'addBank')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="coa_type" class="form-select @error('coa_type', 'addBank') is-invalid @enderror" required>
                                <option value="asset" selected>Asset</option>
                                <option value="liability">Liability</option>
                                <option value="equity">Equity</option>
                                <option value="revenue">Revenue</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                         <div class="col-md-6">
                            <label class="form-label">Subtype</label>
                            <input type="text" name="coa_subtype" class="form-control @error('coa_subtype', 'addBank') is-invalid @enderror" value="{{ old('coa_subtype', 'Cash & Bank') }}" placeholder="e.g. Cash & Bank">
                             @error('coa_subtype', 'addBank')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Bank & Account</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Bank Account -->
<div class="modal fade" id="editBankModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="editBankForm" method="POST">
            @csrf
            @method('PUT')
            
            <!-- Hidden ID to restore state on error -->
            <input type="hidden" name="bank_id" id="edit_bank_id_hidden" value="{{ old('bank_id') }}">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Bank Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Updating these details will also update the name and description of the linked General Ledger account to ensure consistency.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                            <input type="text" name="bank_name" id="edit_bank_name" class="form-control @error('bank_name', 'updateBank') is-invalid @enderror" value="{{ old('bank_name') }}" required>
                            @error('bank_name', 'updateBank')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Holder <span class="text-danger">*</span></label>
                            <input type="text" name="account_name" id="edit_account_name" class="form-control @error('account_name', 'updateBank') is-invalid @enderror" value="{{ old('account_name') }}" required>
                             @error('account_name', 'updateBank')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Number <span class="text-danger">*</span></label>
                            <input type="text" name="account_number" id="edit_account_number" class="form-control @error('account_number', 'updateBank') is-invalid @enderror" value="{{ old('account_number') }}" required>
                             @error('account_number', 'updateBank')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Currency <span class="text-danger">*</span></label>
                            <select name="currency" id="edit_currency" class="form-select @error('currency', 'updateBank') is-invalid @enderror">
                            @foreach($currencies as $code => $name)
                                <option value="{{ $code }}" {{ (old('currency') == $code) ? 'selected' : '' }}>{{ $code }} - {{ $name }}</option>
                            @endforeach
                            </select>
                             @error('currency', 'updateBank')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch Code</label>
                            <input type="text" name="branch_code" id="edit_branch_code" class="form-control @error('branch_code', 'updateBank') is-invalid @enderror" value="{{ old('branch_code') }}">
                             @error('branch_code', 'updateBank')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Swift Code</label>
                            <input type="text" name="swift_code" id="edit_swift_code" class="form-control @error('swift_code', 'updateBank') is-invalid @enderror" value="{{ old('swift_code') }}">
                             @error('swift_code', 'updateBank')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bank Address</label>
                            <textarea name="address" id="edit_address" class="form-control @error('address', 'updateBank') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                             @error('address', 'updateBank')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div> 
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Delete Confirmation -->
<div class="modal fade" id="deleteBankModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger">Confirm Account Removal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>You are about to remove the bank account <strong id="deleteBankName"></strong> (<span id="deleteAccountNumber"></span>).</p>
                
                <div class="alert alert-warning small">
                    <h6 class="alert-heading fw-bold"><i class="bi bi-shield-lock-fill me-1"></i>Compliance Notice (Audit Trail)</h6>
                    <hr>
                    <p class="mt-2 mb-0">
                        This action will only remove the bank configuration settings from this list. The financial records remain preserved in your Chart of Accounts.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteBankForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Confirm Removal</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        // --- Dynamic Business Types Logic ---
        var businessTypesPH = @json($businessTypesPH);
        var businessTypesIntl = @json($businessTypesIntl);
        // Safely get old value or existing DB value
        var selectedBusinessType = "{{ old('business_type', $tenant->business_type) }}";
        
        var countrySelect = document.getElementById('country_select');
        var typeSelect = document.getElementById('business_type_select');

        function populateBusinessTypes() {
            var country = countrySelect.value;
            var options = (country === 'PH') ? businessTypesPH : businessTypesIntl;
            
            // Clear current options
            typeSelect.innerHTML = '<option value="" disabled>Select type...</option>';
            
            for (var key in options) {
                var opt = document.createElement('option');
                opt.value = key;
                opt.innerHTML = options[key];
                
                // Keep selected value if matches
                if (key === selectedBusinessType) {
                    opt.selected = true;
                }
                typeSelect.appendChild(opt);
            }
        }

        if(countrySelect && typeSelect) {
            countrySelect.addEventListener('change', populateBusinessTypes);
            // Initial load
            populateBusinessTypes();
        }

        // --- 1. Auto-Reopen Modals on Error ---
        @if($errors->addBank->any())
            var addModal = new bootstrap.Modal(document.getElementById('addBankModal'));
            addModal.show();
        @endif

        @if($errors->updateBank->any())
            var editModal = new bootstrap.Modal(document.getElementById('editBankModal'));
            // When reopening Edit Modal on error, we need to set the action URL properly
            // We use the hidden input 'bank_id' which was preserved via old()
            var bankId = "{{ old('bank_id') }}";
            var form = document.getElementById('editBankForm');
            // Reconstruct the URL based on the pattern used in the view
            form.action = "{{ route('settings.organization.bank.update', 'ID_PLACEHOLDER') }}".replace('ID_PLACEHOLDER', bankId);
            editModal.show();
        @endif


        // --- 2. DELETE MODAL LOGIC ---
        var deleteBankModal = document.getElementById('deleteBankModal');
        deleteBankModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var actionUrl = button.getAttribute('data-action');
            var bankName = button.getAttribute('data-bank-name');
            var accountNumber = button.getAttribute('data-account-number');

            var form = document.getElementById('deleteBankForm');
            form.action = actionUrl;

            document.getElementById('deleteBankName').textContent = bankName;
            document.getElementById('deleteAccountNumber').textContent = accountNumber;
        });

        // --- 3. EDIT MODAL LOGIC (Clicking Edit Button) ---
        var editBankModal = document.getElementById('editBankModal');
        editBankModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            
            // Only populate if triggered by a button (not by JS error reopening)
            if (button) {
                var updateUrl = button.getAttribute('data-update-url');
                document.getElementById('editBankForm').action = updateUrl;

                // Populate Hidden ID
                document.getElementById('edit_bank_id_hidden').value = button.getAttribute('data-bank-id');

                // Populate Fields
                document.getElementById('edit_bank_name').value = button.getAttribute('data-bank-name');
                document.getElementById('edit_account_name').value = button.getAttribute('data-account-name');
                document.getElementById('edit_account_number').value = button.getAttribute('data-account-number');
                document.getElementById('edit_currency').value = button.getAttribute('data-currency');
                document.getElementById('edit_branch_code').value = button.getAttribute('data-branch-code');
                document.getElementById('edit_swift_code').value = button.getAttribute('data-swift-code');
                document.getElementById('edit_address').value = button.getAttribute('data-address');
                
                // Clear any existing validation errors when opening fresh
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            }
        });
    });
</script>

@endsection
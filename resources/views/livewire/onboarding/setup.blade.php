<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0 fw-bold text-primary">Let's set up your organization</h4>
                    <p class="text-muted mb-0 small">Tell us about your business to get started.</p>
                </div>
                
                <div class="card-body p-4">
                    <form wire:submit.prevent="completeSetup">
                        
                        <!-- Section: Personal Profile -->
                        <h6 class="text-uppercase text-secondary fw-bold small border-bottom pb-2 mb-3">1. Administrator Profile</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" wire:model="first_name" class="form-control" placeholder="John">
                                @error('first_name') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" wire:model="last_name" class="form-control" placeholder="Doe">
                                @error('last_name') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" wire:model="phone" class="form-control" placeholder="+63 900 000 0000">
                                @error('phone') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Job Position</label>
                                <select wire:model="position" class="form-select">
                                    <option value="">Select Position...</option>
                                    @foreach($this->positions as $pos)
                                        <option value="{{ $pos }}">{{ $pos }}</option>
                                    @endforeach
                                </select>
                                @error('position') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Section: Company Details -->
                        <h6 class="text-uppercase text-secondary fw-bold small border-bottom pb-2 mb-3">2. Business Entity</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Company / Registered Name</label>
                                <input type="text" wire:model="company_name" class="form-control" placeholder="e.g. Syneria Solutions Inc.">
                                @error('company_name') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label">Trade Name <span class="text-muted small">(Optional)</span></label>
                                <input type="text" wire:model="trade_name" class="form-control" placeholder="Doing business as...">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Country of Operation</label>
                                <select wire:model.live="country" class="form-select">
                                    <option value="PH">Philippines</option>
                                    @foreach($this->countries as $code => $name)
                                        @if($code !== 'PH') <option value="{{ $code }}">{{ $name }}</option> @endif
                                    @endforeach
                                </select>
                                @error('country') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Business Type</label>
                                <select wire:model="business_type" class="form-select">
                                    <option value="">Select Type...</option>
                                    @foreach($this->businessTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('business_type') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Company Registration Number</label>
                                <input type="text" wire:model="company_reg_number" class="form-control" placeholder="e.g. 000-000-000-000">
                                @error('company_reg_number') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tax Identification Number</label>
                                <input type="text" wire:model="tax_identification_number" class="form-control" placeholder="e.g. 000-000-000-000">
                                @error('tax_identification_number') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Registered Business Address</label>
                                <textarea wire:model="business_address" class="form-control" rows="2" placeholder="e.g. 47 W 13th St"></textarea>
                                @error('business_address') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">City / Municipality</label>
                                <input type="text" wire:model="city" class="form-control" placeholder="e.g. New York">
                                @error('city') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Postal Code</label>
                                <input type="number" wire:model="postal_code" class="form-control" placeholder="e.g. 10011">
                                @error('postal_code') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="col-12 mt-4">
                                <div class="p-3 border rounded bg-light">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" wire:model="import_coa" id="importCoaCheck">
                                        <label class="form-check-label" for="importCoaCheck">
                                            <strong>Import Default Chart of Accounts?</strong><br>
                                            <small class="text-muted">To help you get started quickly, we’ll set up a country-specific standard list of accounts (Assets, Liabilities, Expenses). You can customize it at any time.</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                                                    
                        </div>

                        <div class="d-flex justify-content-end border-top pt-3">
                            <button type="submit" class="btn btn-primary px-4" wire:loading.attr="disabled">
                                <span wire:loading.remove>Create Organization</span>
                                <span wire:loading>Processing...</span>
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
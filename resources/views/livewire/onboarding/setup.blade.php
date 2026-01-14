<div class="row justify-content-center mt-4">
    <div class="col-md-7"> <!-- Widened slightly for address -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h4 class="fw-bold text-center">Tell us about you and your business</h4>
            </div>
            <div class="card-body p-4">
                <form wire:submit.prevent="completeSetup">
                    
                    <h6 class="text-uppercase text-muted fw-bold small mb-3">Personal Details</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input wire:model="first_name" type="text" class="form-control" placeholder="e.g. John">
                            @error('first_name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input wire:model="last_name" type="text" class="form-control" placeholder="e.g. Doe">
                            @error('last_name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input wire:model="phone" type="text" class="form-control" placeholder="e.g. +63 123 456 789">
                            @error('phone') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Position</label>
                            <select wire:model="position" class="form-select">
                                <option value="">Select Position</option>
                                @foreach($this->positions as $pos)
                                    <option value="{{ $pos }}">{{ $pos }}</option>
                                @endforeach
                            </select>
                            @error('position') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <hr class="my-4">
                    
                    <h6 class="text-uppercase text-muted fw-bold small mb-3">Organization Details</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Business Name (Official Registered Name)</label>
                        <input wire:model="company_name" type="text" class="form-control" placeholder="e.g. Acme Solutions Inc.">
                        @error('company_name') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Trade Name / DBA (Optional)</label>
                            <input wire:model="trade_name" type="text" class="form-control" placeholder="e.g. Acme Cafe">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Registration Number</label>
                            <input wire:model="company_reg_number" type="text" class="form-control" placeholder="e.g. 000-000-000-000">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Company TIN</label>
                            <input wire:model="tax_identification_number" type="text" class="form-control" placeholder="e.g. 000-000-000-000">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type of Business</label>
                            <select wire:model="business_type" class="form-select">
                                <option value="">Select Type...</option>
                                @foreach($this->businessTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('business_type') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Location / Country</label>
                        <select wire:model.live="country" class="form-select">
                            <option value="">Select Country</option>
                            @foreach($this->countries as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Official Business Address</label>
                        <textarea wire:model="business_address" class="form-control" rows="2" placeholder="e.g. 123 Main Street, Anytown, USA, 12345"></textarea>
                        @error('business_address') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">City</label>
                            <input wire:model="city" type="text" class="form-control" placeholder="e.g. Any City">
                            @error('city') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Postal Code</label>
                            <input wire:model="postal_code" type="text" class="form-control" placeholder="e.g. 12345">
                            @error('postal_code') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-success btn-lg text-white">Start Using SyneriaBooks</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
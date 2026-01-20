@extends('layouts.app')

@section('title', 'Edit Contact - SyneriaBooks')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Edit Contact</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('contacts.update', $contact->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Contact Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select" required>
                                    <option value="customer" {{ (old('type', $contact->type) == 'customer') ? 'selected' : '' }}>Customer</option>
                                    <option value="supplier" {{ (old('type', $contact->type) == 'supplier') ? 'selected' : '' }}>Supplier</option>
                                    <option value="employee" {{ (old('type', $contact->type) == 'employee') ? 'selected' : '' }}>Employee</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $contact->name) }}" required placeholder="e.g. John Doe">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tax Number (TIN) <span class="text-danger">*</span></label>
                                <input type="text" name="tax_number" class="form-control" value="{{ old('tax_number', $contact->tax_number) }}" placeholder="e.g. 000-000-000" required>
                            </div>                            

                            <div class="col-md-6">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $contact->company_name) }}" placeholder="e.g. ABC Corp">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $contact->email) }}" placeholder="e.g. me@abc.com">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $contact->phone ?? '') }}" placeholder="e.g. +63 123 123 4567">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2" placeholder="e.g. 47 W 13th St. New York, 10111, USA">{{ old('address', $contact->address) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <a href="{{ route('contacts.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Contact</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
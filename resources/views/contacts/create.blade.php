@extends('layouts.app')

@section('title', 'New Contact - SyneriaBooks')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">New Contact</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('contacts.store') }}" method="POST">
                        @csrf
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Contact Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select" required>
                                    <option value="customer">Customer</option>
                                    <option value="supplier">Supplier</option>
                                    <option value="employee">Employee</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required placeholder="e.g. John Doe" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tax Number (TIN) <span class="text-danger">*</span></label>
                                <input type="text" name="tax_number" class="form-control" placeholder="e.g. 000-000-000" required>
                            </div>                            

                            <div class="col-md-6">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" class="form-control" placeholder="e.g. ABC Corp">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="e.g. me@abc.com">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="e.g. +63 123 123 4567">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2" placeholder="e.g. 47 W 13th St. New York, 10111, USA"></textarea>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <a href="{{ route('contacts.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Contact</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
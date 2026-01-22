@extends('layouts.app')

@section('title', 'Tax Rates - SyneriaBooks')

@section('content')
<div class="container-fluid py-4">
    <!-- Header: Title, Search, and Add Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 text-dark fw-bold">Tax Rates</h2>
            <p class="text-muted small mb-0">Manage tax codes and percentages for transactions.</p>
        </div>
        
        <div class="d-flex gap-2">
            <!-- Search Form -->
            <form action="{{ route('settings.tax_rates.index') }}" method="GET" class="d-flex">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search rates..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                    @if(request('search'))
                        <a href="{{ route('settings.tax_rates.index') }}" class="btn btn-outline-secondary" title="Clear Search">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>

            <!-- New Tax Rate Button -->
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createTaxModal">
                <i class="bi bi-plus-lg me-1"></i> New Tax Rate
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

    <!-- Tax Rates Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive" style="min-height: 300px;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Tax Name</th>
                            <th>Code</th>
                            <th class="text-end">Rate (%)</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($taxRates as $tax)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold">{{ $tax->name }}</div>
                            </td>
                            <td>
                                <span class="font-monospace text-muted small bg-light border px-2 py-1 rounded">{{ $tax->code }}</span>
                            </td>
                            <td class="text-end fw-bold">
                                {{ number_format($tax->rate * 100, 2) }}%
                            </td>
                            <td>
                                @if($tax->type === 'sales')
                                    <span class="badge bg-info-subtle text-info">Sales</span>
                                @elseif($tax->type === 'purchase')
                                    <span class="badge bg-warning-subtle text-warning">Purchase</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Both</span>
                                @endif
                            </td>
                            <td>
                                @if($tax->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <!-- Edit -->
                                        <li>
                                            <button type="button" class="dropdown-item edit-tax-btn"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editTaxModal"
                                                    data-id="{{ $tax->id }}"
                                                    data-name="{{ $tax->name }}"
                                                    data-code="{{ $tax->code }}"
                                                    data-rate="{{ $tax->rate * 100 }}" {{-- Send as 10, not 0.10 --}}
                                                    data-type="{{ $tax->type }}"
                                                    data-update-url="{{ route('settings.tax_rates.update', $tax->id) }}">
                                                <i class="bi bi-pencil me-2"></i>Edit Rate
                                            </button>
                                        </li>
                                        
                                        <li><hr class="dropdown-divider"></li>
                                        
                                        <!-- Toggle Status -->
                                        <li>
                                            <form action="{{ route('settings.tax_rates.toggle', $tax->id) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="dropdown-item {{ $tax->is_active ? 'text-danger' : 'text-success' }}">
                                                    @if($tax->is_active)
                                                        <i class="bi bi-pause-circle me-2"></i>Deactivate
                                                    @else
                                                        <i class="bi bi-play-circle me-2"></i>Activate
                                                    @endif
                                                </button>
                                            </form>
                                        </li>
                                        
                                        <!-- Delete -->
                                        <li>
                                            <form action="{{ route('settings.tax_rates.destroy', $tax->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Delete this tax rate completely?')">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-percent fs-1 d-block mb-2"></i>
                                No tax rates found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        @if($taxRates->hasPages())
        <div class="card-footer bg-white">
            {{ $taxRates->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createTaxModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('settings.tax_rates.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">New Tax Rate</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Tax Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required placeholder="e.g. VAT Standard">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tax Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="code" required placeholder="e.g. VAT-S">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Rate (%) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" max="100" class="form-control" name="rate" required placeholder="0.00">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Applies To</label>
                                <select name="type" class="form-select">
                                    <option value="both">Both Sales & Purchases</option>
                                    <option value="sales">Sales Only</option>
                                    <option value="purchase">Purchases Only</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Rate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editTaxModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editTaxForm" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Tax Rate</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Tax Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editName" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tax Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editCode" name="code" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Rate (%) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" max="100" class="form-control" id="editRate" name="rate" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Applies To</label>
                                <select name="type" id="editType" class="form-select">
                                    <option value="both">Both Sales & Purchases</option>
                                    <option value="sales">Sales Only</option>
                                    <option value="purchase">Purchases Only</option>
                                </select>
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
        var editButtons = document.querySelectorAll('.edit-tax-btn');
        var editForm = document.getElementById('editTaxForm');
        
        var inputName = document.getElementById('editName');
        var inputCode = document.getElementById('editCode');
        var inputRate = document.getElementById('editRate');
        var inputType = document.getElementById('editType');

        editButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                var url = this.getAttribute('data-update-url');
                var name = this.getAttribute('data-name');
                var code = this.getAttribute('data-code');
                var rate = this.getAttribute('data-rate');
                var type = this.getAttribute('data-type');

                editForm.action = url;
                inputName.value = name;
                inputCode.value = code;
                inputRate.value = rate; // Already formatted as whole number (e.g. 10) in blade
                inputType.value = type;
            });
        });
    });
</script>
@endsection
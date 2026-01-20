@extends('layouts.app')

@php
    $label = ($type === 'invoice') ? 'Sales Invoices' : 'Purchase Bills';
    $contactLabel = ($type === 'invoice') ? 'Customer' : 'Supplier';
@endphp

@section('title', $label . ' - SyneriaBooks')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 text-dark fw-bold">{{ $label }}</h2>
            <p class="text-muted small mb-0">Manage your {{ strtolower($label) }} and tax statuses.</p>
        </div>
        
        <div class="d-flex gap-2">
            {{-- Search Form --}}
            <form action="{{ route('invoices.index') }}" method="GET" class="d-flex">
                <input type="hidden" name="type" value="{{ $type }}">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search Number or Ref..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                    @if(request('search'))
                        <a href="{{ route('invoices.index', ['type' => $type]) }}" class="btn btn-outline-secondary" title="Clear Search">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>

            <a href="{{ route('invoices.create', ['type' => $type]) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New {{ ucfirst($type) }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive" style="min-height: 300px;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Number</th>
                            <th>Reference</th>
                            <th>Type</th>
                            <th>{{ $contactLabel }}</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $inv)
                        <tr>
                            <td class="ps-4">{{ $inv->date->format('Y-m-d') }}</td>
                            <td class="font-monospace fw-bold text-primary">
                                {{ $inv->number ?? 'DRAFT' }}
                            </td>
                            <td class="small text-muted">{{ $inv->reference ?? '--' }}</td>
                            <td>
                                {{-- Subtype Badge --}}
                                @if($inv->subtype === 'sales_invoice')
                                    <span class="badge border border-primary text-primary bg-light">Goods (SI)</span>
                                @elseif($inv->subtype === 'service_invoice')
                                    <span class="badge border border-info text-info bg-light">Service (BI)</span>
                                @else
                                    <span class="badge border border-secondary text-secondary bg-light">Standard</span>
                                @endif
                                
                                {{-- Tax Badge (Small) --}}
                                @if($inv->tax_type === 'non_vat')
                                    <span class="badge text-bg-warning ms-1" style="font-size: 0.65rem;">Non-VAT</span>
                                @elseif($inv->tax_type === 'vat_exempt')
                                    <span class="badge text-bg-secondary ms-1" style="font-size: 0.65rem;">Exempt</span>
                                @elseif($inv->tax_type === 'zero_rated')
                                    <span class="badge text-bg-dark ms-1" style="font-size: 0.65rem;">0-Rated</span>
                                @endif
                            </td>
                            <td>
                                {{ $inv->contact->name ?? 'Unknown' }}
                            </td>
                            <td class="text-end fw-bold font-monospace">
                                {{ number_format($inv->grand_total, 2) }}
                            </td>
                            <td class="text-center">
                                @if($inv->status === 'posted')
                                    <span class="badge bg-success-subtle text-success">Posted</span>
                                @elseif($inv->status === 'draft')
                                    <span class="badge bg-secondary-subtle text-secondary">Draft</span>
                                @elseif($inv->status === 'voided')
                                    <span class="badge bg-danger-subtle text-danger">Voided</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('invoices.show', $inv->id) }}" target="_blank">
                                                <i class="bi bi-file-earmark-pdf me-2"></i>View PDF
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>                                          
                                        @if($inv->status === 'draft')
                                            <li><a class="dropdown-item" href="{{ route('invoices.edit', ['invoice' => $inv->id, 'type' => $type]) }}">
                                                <i class="bi bi-pencil me-2"></i>Edit
                                            </a></li>
                                            {{-- DELETE ACTION TRIGGER --}}
                                            <li>
                                                <button type="button" class="dropdown-item text-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal" 
                                                        data-action="{{ route('invoices.destroy', $inv->id) }}">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </button>
                                            </li>
                                        @elseif($inv->status === 'posted')
                                            {{-- VOID ACTION TRIGGER --}}
                                            <li>
                                                <button type="button" class="dropdown-item text-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#voidModal" 
                                                        data-action="{{ route('invoices.void', $inv->id) }}">
                                                    <i class="bi bi-slash-circle me-2"></i>Void
                                                </button>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-receipt fs-1 d-block mb-2"></i>
                                No records found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $invoices->appends(['type' => $type])->links() }}
        </div>
    </div>
</div>

{{-- CONFIRMATION MODALS --}}

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this draft? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Draft</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Void Modal --}}
<div class="modal fade" id="voidModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Void</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to void this invoice? The series number will be retained for audit purposes.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="voidForm" method="POST" action="">
                    @csrf
                    <button type="submit" class="btn btn-danger">Void Invoice</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Handle Delete Modal
        var deleteModal = document.getElementById('deleteModal');
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var actionUrl = button.getAttribute('data-action');
            var form = deleteModal.querySelector('#deleteForm');
            form.action = actionUrl;
        });

        // Handle Void Modal
        var voidModal = document.getElementById('voidModal');
        voidModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var actionUrl = button.getAttribute('data-action');
            var form = voidModal.querySelector('#voidForm');
            form.action = actionUrl;
        });
    });
</script>

@endsection
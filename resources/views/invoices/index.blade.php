@extends('layouts.app')

@php
    $label = ($type === 'invoice') ? 'Sales Invoices' : 'Purchase Bills';
    $singularLabel = ($type === 'invoice') ? 'Invoice' : 'Bill';
@endphp

@section('title', $label . ' - SyneriaBooks')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 text-dark fw-bold">{{ $label }}</h2>
            <p class="text-muted small mb-0">Manage your {{ strtolower($label) }} and workflow.</p>
        </div>
        
        <div class="d-flex gap-2">
            <form action="{{ route('invoices.index') }}" method="GET" class="d-flex gap-2">
                <input type="hidden" name="type" value="{{ $type }}">
                
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 140px; border-color: #ced4da;">
                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="review" {{ request('status') == 'review' ? 'selected' : '' }}>For Review</option>
                    <option value="reviewed" {{ request('status') == 'reviewed' ? 'selected' : '' }}>Ready to Send</option>
                    <option value="posted" {{ request('status') == 'posted' ? 'selected' : '' }}>Posted/Sent</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="voided" {{ request('status') == 'voided' ? 'selected' : '' }}>Voided</option>
                </select>

                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search Number, Ref, Name..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                    @if(request('search') || (request('status') && request('status') !== 'all'))
                        <a href="{{ route('invoices.index', ['type' => $type]) }}" class="btn btn-outline-secondary" title="Clear Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>

            <a href="{{ route('invoices.create', ['type' => $type]) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New {{ $singularLabel }}
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
                            <th>{{ ($type === 'invoice') ? 'Customer' : 'Supplier' }}</th>
                            <th>Due Date</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $inv)
                        @php
                            $isOverdue = $inv->due_date < now() && !in_array($inv->status, ['paid', 'voided', 'draft']);
                        @endphp
                        <tr>
                            <td class="ps-4">{{ $inv->date->format('Y-m-d') }}</td>
                            <td>
                                @if($inv->number)
                                    <span class="font-monospace fw-bold text-primary">{{ $inv->number }}</span>
                                @else
                                    <span class="text-muted small">---</span>
                                @endif
                                @if($inv->is_recurring)
                                    <i class="bi bi-arrow-repeat text-success ms-1" title="Recurring Invoice"></i>
                                @endif
                            </td>
                            <td><span class="text-muted small">{{ $inv->reference }}</span></td>
                            <td>{{ $inv->contact->name }}</td>
                            
                            {{-- Due Date Logic --}}
                            <td>
                                @if($isOverdue)
                                    <span class="text-danger fw-bold small">
                                        {{ $inv->due_date->format('M d') }}
                                        <i class="bi bi-exclamation-circle-fill ms-1" title="Overdue"></i>
                                    </span>
                                @else
                                    <span class="text-muted small">{{ $inv->due_date->format('M d') }}</span>
                                @endif
                            </td>

                            <td class="text-end fw-bold">
                                {{ $inv->currency_code ?? '' }} {{ number_format($inv->grand_total, 2) }}
                            </td>
                            <td class="text-center">
                                @php
                                    $badge = match($inv->status) {
                                        'draft' => 'bg-secondary-subtle text-secondary',
                                        'review' => 'bg-warning-subtle text-warning',
                                        'reviewed' => 'bg-success-subtle text-success',
                                        'posted' => 'bg-primary-subtle text-primary',
                                        'paid' => 'bg-success text-white',
                                        'voided' => 'bg-danger-subtle text-danger',
                                        default => 'bg-light text-muted'
                                    };
                                    $statusLabel = ($inv->status === 'reviewed') ? 'Ready' : ucfirst($inv->status);
                                @endphp
                                <span class="badge {{ $badge }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-boundary="viewport">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('invoices.show', $inv->id) }}" target="_blank">
                                                <i class="bi bi-file-earmark-pdf me-2"></i>View PDF/Print
                                            </a>
                                        </li>     
                                        <li><hr class="dropdown-divider"></li>                                   
                                        
                                        @if($inv->status === 'draft' && $isBookkeeper)
                                            <li>
                                                <a class="dropdown-item" href="{{ route('invoices.edit', $inv->id) }}">
                                                    <i class="bi bi-pencil me-2"></i>Edit
                                                </a>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item text-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#confirmActionModal"
                                                    data-action="{{ route('invoices.submit', $inv->id) }}"
                                                    data-method="POST"
                                                    data-title="Submit for Review"
                                                    data-message="Submit this draft for review?"
                                                    data-confirm-text="Submit">
                                                    <i class="bi bi-arrow-right-circle me-2"></i>Submit
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item text-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#confirmActionModal"
                                                    data-action="{{ route('invoices.destroy', $inv->id) }}"
                                                    data-method="DELETE"
                                                    data-title="Delete Draft"
                                                    data-message="Are you sure you want to delete this draft?"
                                                    data-confirm-text="Delete Draft">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </button>
                                            </li>
                                        @elseif($inv->status === 'review' && $isReviewer)
                                            <li>
                                                <a class="dropdown-item" href="{{ route('invoices.edit', $inv->id) }}">
                                                    <i class="bi bi-pencil me-2"></i>Edit/Correct
                                                </a>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item text-success" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#confirmActionModal"
                                                    data-action="{{ route('invoices.approve', $inv->id) }}"
                                                    data-method="POST"
                                                    data-title="Approve Invoice"
                                                    data-message="Mark this invoice as Approved/Reviewed?"
                                                    data-confirm-text="Approve">
                                                    <i class="bi bi-check-circle me-2"></i>Approve
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item text-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#confirmActionModal"
                                                    data-action="{{ route('invoices.reject', $inv->id) }}"
                                                    data-method="POST"
                                                    data-title="Reject Invoice"
                                                    data-message="Return this invoice to Draft status?"
                                                    data-confirm-text="Reject">
                                                    <i class="bi bi-x-circle me-2"></i>Reject
                                                </button>
                                            </li>
                                        @elseif($inv->status === 'reviewed' && $isApprover)
                                            <li>
                                                <button type="button" class="dropdown-item text-primary fw-bold" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#confirmActionModal"
                                                    data-action="{{ route('invoices.send', $inv->id) }}"
                                                    data-method="POST"
                                                    data-title="Post & Send"
                                                    data-message="Generate Invoice Number and Post to ledger?"
                                                    data-confirm-text="Post & Send">
                                                    <i class="bi bi-send me-2"></i>Post & Send
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item text-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#confirmActionModal"
                                                    data-action="{{ route('invoices.reject', $inv->id) }}"
                                                    data-method="POST"
                                                    data-title="Reject Invoice"
                                                    data-message="Return this invoice to Draft status?"
                                                    data-confirm-text="Reject">
                                                    <i class="bi bi-x-circle me-2"></i>Reject
                                                </button>
                                            </li>
                                        @elseif(in_array($inv->status, ['posted', 'paid']) && $isApprover)
                                            <li>
                                                <button type="button" class="dropdown-item text-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#confirmActionModal"
                                                    data-action="{{ route('invoices.void', $inv->id) }}"
                                                    data-method="POST"
                                                    data-title="Void Invoice"
                                                    data-message="Voiding cannot be undone. Proceed?"
                                                    data-confirm-text="Void">
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
                                No invoices found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($invoices->hasPages())
        <div class="card-footer bg-white">
            {{ $invoices->links() }} 
        </div>
        @endif
    </div>
</div>

{{-- Generic Confirmation Modal (Same as before) --}}
<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage" class="mb-0">Are you sure?</p>
            </div>
            <div class="modal-footer">
                <form id="confirmActionForm" method="POST" action="">
                    @csrf
                    <div id="methodInputContainer"></div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="confirmActionSubmit">Confirm</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var confirmModal = document.getElementById('confirmActionModal');
    confirmModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var actionUrl = button.getAttribute('data-action');
        var method = button.getAttribute('data-method');
        var title = button.getAttribute('data-title');
        var message = button.getAttribute('data-message');
        var confirmText = button.getAttribute('data-confirm-text');

        var modalTitle = confirmModal.querySelector('.modal-title');
        var modalBody = confirmModal.querySelector('#confirmMessage');
        var modalForm = confirmModal.querySelector('#confirmActionForm');
        var submitBtn = confirmModal.querySelector('#confirmActionSubmit');
        var methodInputContainer = confirmModal.querySelector('#methodInputContainer');

        modalTitle.textContent = title;
        modalBody.textContent = message;
        modalForm.action = actionUrl;
        submitBtn.textContent = confirmText;

        methodInputContainer.innerHTML = '';
        if (method === 'DELETE') {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_method';
            input.value = 'DELETE';
            methodInputContainer.appendChild(input);
        }
    });
});
</script>
@endsection
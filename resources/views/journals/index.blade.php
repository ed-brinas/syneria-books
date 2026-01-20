@extends('layouts.app')

@section('title', 'Journal Entries - SyneriaBooks')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 text-dark fw-bold">Journal Entries</h2>
        
        <div class="d-flex gap-2">
            {{-- Search Form --}}
            <form action="{{ route('journals.index') }}" method="GET" class="d-flex">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search entries..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                    @if(request('search'))
                        <a href="{{ route('journals.index') }}" class="btn btn-outline-secondary" title="Clear Search">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>

            <a href="{{ route('journals.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Journal Entry
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
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
                            <th>Reference</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                        <tr>
                            <td class="ps-4">{{ $entry->date->format('Y-m-d') }}</td>
                            <td>
                                @if($entry->reference)
                                    <span class="font-monospace fw-bold">{{ $entry->reference }}</span>
                                @else
                                    <span class="text-muted small">--</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-truncate" style="max-width: 300px;">{{ $entry->description }}</div>
                                <small class="text-muted">
                                    {{ $entry->lines->count() }} lines
                                </small>
                            </td>
                            <td class="text-end fw-bold font-monospace">
                                {{ number_format($entry->total_debit, 2) }}
                            </td>
                            <td class="text-center">
                                @if($entry->status === 'posted')
                                    <span class="badge bg-success-subtle text-success">Posted</span>
                                @elseif($entry->status === 'draft')
                                    <span class="badge bg-secondary-subtle text-secondary">Draft</span>
                                @elseif($entry->status === 'voided')
                                    <span class="badge bg-danger-subtle text-danger">Voided</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-boundary="viewport">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('journals.show', $entry->id) }}" target="_blank">
                                                <i class="bi bi-file-earmark-pdf me-2"></i>View PDF/Print
                                            </a>
                                        </li>     
                                        <li><hr class="dropdown-divider"></li>                                   
                                        {{-- Logic 1: Draft Actions --}}
                                        @if($entry->status === 'draft')
                                            <li><h6 class="dropdown-header">Draft Actions</h6></li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('journals.edit', $entry->id) }}">
                                                    <i class="bi bi-pencil me-2"></i>Edit Draft
                                                </a>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item text-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#confirmActionModal"
                                                    data-action="{{ route('journals.destroy', $entry->id) }}"
                                                    data-method="DELETE"
                                                    data-title="Delete Draft"
                                                    data-message="Are you sure you want to delete this draft permanently? This cannot be undone."
                                                    data-confirm-text="Delete Draft">
                                                    <i class="bi bi-trash me-2"></i>Delete Draft
                                                </button>
                                            </li>
                                        
                                        {{-- Logic 2: Posted Actions --}}
                                        @elseif($entry->status === 'posted')
                                            <li><h6 class="dropdown-header">Compliance Actions</h6></li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('journals.reverse', $entry->id) }}">
                                                    <i class="bi bi-arrow-counterclockwise me-2"></i>Reverse Entry
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button type="button" class="dropdown-item text-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#confirmActionModal"
                                                    data-action="{{ route('journals.void', $entry->id) }}"
                                                    data-method="POST"
                                                    data-title="Void Entry"
                                                    data-message="Voiding cannot be undone. The entry will remain in the system for audit purposes but amounts will be ignored. Proceed?"
                                                    data-confirm-text="Void Entry">
                                                    <i class="bi bi-slash-circle me-2"></i>Void Entry
                                                </button>
                                            </li>
                                        @endif
                                        
                                        @if($entry->status === 'voided')
                                            <li><span class="dropdown-item-text text-muted small">No actions available</span></li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-album fs-1 d-block mb-2"></i>
                                No journal entries found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($entries->hasPages())
        <div class="card-footer bg-white">
            {{ $entries->appends(request()->query())->links() }} {{-- Use appends to keep search query in pagination links --}}
        </div>
        @endif
    </div>
</div>

{{-- Generic Confirmation Modal --}}
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
                    {{-- Hidden input for method spoofing (DELETE) --}}
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
        
        // Extract info from data-* attributes
        var actionUrl = button.getAttribute('data-action');
        var method = button.getAttribute('data-method');
        var title = button.getAttribute('data-title');
        var message = button.getAttribute('data-message');
        var confirmText = button.getAttribute('data-confirm-text');

        // Update Modal Content
        var modalTitle = confirmModal.querySelector('.modal-title');
        var modalBody = confirmModal.querySelector('#confirmMessage');
        var modalForm = confirmModal.querySelector('#confirmActionForm');
        var submitBtn = confirmModal.querySelector('#confirmActionSubmit');
        var methodInputContainer = confirmModal.querySelector('#methodInputContainer');

        modalTitle.textContent = title;
        modalBody.textContent = message;
        modalForm.action = actionUrl;
        submitBtn.textContent = confirmText;

        // Handle Method Spoofing for DELETE
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
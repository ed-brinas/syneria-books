@extends('layouts.app')

@section('title', 'Contacts - SyneriaBooks')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 text-dark fw-bold">Contacts</h2>
        
        <div class="d-flex gap-2">
            <!-- Search Form -->
            <form action="{{ route('contacts.index') }}" method="GET" class="d-flex">
                @if(request('type'))
                    <input type="hidden" name="type" value="{{ request('type') }}">
                @endif
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search contacts..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                    @if(request('search'))
                        <a href="{{ route('contacts.index', ['type' => request('type')]) }}" class="btn btn-outline-danger" title="Clear Search">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>

            <a href="{{ route('contacts.create') }}" class="btn btn-primary d-flex align-items-center">
                <i class="bi bi-plus-lg me-1"></i> New
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive" style="min-height: 200px;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Name / Company</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Email</th>
                            <th>TIN / Tax #</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contacts as $contact)
                        <tr class="{{ $contact->trashed() ? 'bg-light text-muted' : '' }}">
                            <td class="ps-4">
                                <div class="fw-bold">{{ $contact->name }}</div>
                                @if($contact->company_name)
                                    <small class="{{ $contact->trashed() ? 'text-muted' : 'text-muted' }}">{{ $contact->company_name }}</small>
                                @endif
                            </td>
                            <td>
                                @if($contact->type === 'customer')
                                    <span class="badge bg-primary-subtle text-primary">Customer</span>
                                @elseif($contact->type === 'supplier')
                                    <span class="badge bg-warning-subtle text-warning">Supplier</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($contact->type) }}</span>
                                @endif
                            </td>
                            <td>
                                @if($contact->trashed())
                                    <span class="badge bg-secondary">Disabled</span>
                                @else
                                    <span class="badge bg-success">Active</span>
                                @endif
                            </td>
                            <td>{{ $contact->email ?? '--' }}</td>
                            <td>{{ $contact->tax_number ?? '--' }}</td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('contacts.edit', $contact->id) }}">
                                                <i class="bi bi-pencil me-2"></i>Edit
                                            </a>
                                        </li>
                                        <li>
                                            @if($contact->trashed())
                                                <button type="button" 
                                                        class="dropdown-item text-success" 
                                                        onclick="confirmAction('{{ route('contacts.restore', $contact->id) }}', 'enable', '{{ addslashes($contact->name) }}')">
                                                    <i class="bi bi-check-circle me-2"></i>Enable / Restore
                                                </button>
                                            @else
                                                <button type="button" 
                                                        class="dropdown-item text-danger" 
                                                        onclick="confirmAction('{{ route('contacts.destroy', $contact->id) }}', 'disable', '{{ addslashes($contact->name) }}')">
                                                    <i class="bi bi-archive me-2"></i>Disable / Archive
                                                </button>
                                            @endif
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                @if(request('search'))
                                    No contacts found matching "<strong>{{ request('search') }}</strong>".
                                    <div class="mt-2">
                                        <a href="{{ route('contacts.index', ['type' => request('type')]) }}" class="text-decoration-none">Clear search</a>
                                    </div>
                                @else
                                    No contacts found.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalTitle">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalMessage">Are you sure you want to perform this action?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="actionForm" method="POST" action="">
                    @csrf
                    <!-- This container will hold the DELETE method input if needed -->
                    <div id="methodContainer"></div>
                    <button type="submit" class="btn btn-primary" id="confirmBtn">Yes, Proceed</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmAction(url, actionType, name) {
        const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
        const form = document.getElementById('actionForm');
        const methodContainer = document.getElementById('methodContainer');
        const message = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('confirmBtn');
        const title = document.getElementById('modalTitle');

        // Set the form action URL
        form.action = url;

        if (actionType === 'disable') {
            // Configure for Disable (DELETE)
            title.textContent = 'Disable Contact';
            message.innerHTML = `Are you sure you want to disable <strong>${name}</strong>? <br><small class="text-muted">This will archive the contact for compliance purposes.</small>`;
            confirmBtn.textContent = 'Disable Contact';
            confirmBtn.className = 'btn btn-danger';
            // Add hidden DELETE method input
            methodContainer.innerHTML = '<input type="hidden" name="_method" value="DELETE">';
        } else {
            // Configure for Enable (POST)
            title.textContent = 'Enable Contact';
            message.innerHTML = `Are you sure you want to enable/restore <strong>${name}</strong>?`;
            confirmBtn.textContent = 'Enable Contact';
            confirmBtn.className = 'btn btn-success';
            // Remove hidden method input (default is POST)
            methodContainer.innerHTML = '';
        }

        modal.show();
    }
</script>
@endsection
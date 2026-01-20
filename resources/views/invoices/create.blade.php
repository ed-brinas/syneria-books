@extends('layouts.app')

@php
    $label = ($type === 'invoice') ? 'Sales Invoice' : 'Purchase Bill';
    $contactLabel = ($type === 'invoice') ? 'Customer' : 'Supplier';
    $contactType = ($type === 'invoice') ? 'customer' : 'supplier';
    
    // Check if we are editing an existing invoice
    $isEdit = isset($invoice);
    $pageTitle = $isEdit ? 'Edit ' . $label : 'New ' . $label;
    $action = $isEdit ? route('invoices.update', $invoice->id) : route('invoices.store');
    
    // Default Values
    $defaultDate = $isEdit ? $invoice->date->format('Y-m-d') : date('Y-m-d');
    $defaultDueDate = $isEdit ? $invoice->due_date->format('Y-m-d') : date('Y-m-d', strtotime('+30 days'));
@endphp

@section('title', $pageTitle . ' - SyneriaBooks')

@section('content')
<div class="container-fluid py-4">
    <form action="{{ $action }}" method="POST" id="invoiceForm">
        @csrf
        @if($isEdit) @method('PUT') @endif
        
        <input type="hidden" name="type" value="{{ $type }}">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 text-dark fw-bold">
                {{ $pageTitle }} 
                @if($isEdit && $invoice->number) <span class="text-muted ms-2">#{{ $invoice->number }}</span> @endif
            </h2>
            <div class="btn-group">
                <a href="{{ route('invoices.index', ['type' => $type]) }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                
                @if(!$isEdit || $invoice->status === 'draft')
                    <button type="submit" name="status" value="draft" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-file-earmark"></i> Save Draft
                    </button>
                    
                    <button type="submit" name="status" value="posted" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg"></i> Post {{ ucfirst($type) }}
                    </button>
                @endif
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">
                    @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Header Information --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                {{-- Document Classification Row --}}
                <div class="row g-3 mb-3 border-bottom pb-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Invoice Classification</label>
                        <select name="subtype" class="form-select form-select-sm" required>
                            <option value="standard" {{ (old('subtype', $invoice->subtype ?? '') == 'standard') ? 'selected' : '' }}>Standard Invoice (General)</option>
                            @if($type === 'invoice')
                            <option value="sales_invoice" {{ (old('subtype', $invoice->subtype ?? '') == 'sales_invoice') ? 'selected' : '' }}>Sales Invoice (Goods)</option>
                            <option value="service_invoice" {{ (old('subtype', $invoice->subtype ?? '') == 'service_invoice') ? 'selected' : '' }}>Billing Invoice (Services)</option>
                            @endif
                        </select>
                        <div class="form-text small" style="font-size: 0.75rem;">Select 'Sales' for Goods, 'Billing' for Services.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Tax Treatment</label>
                        <select name="tax_type" class="form-select form-select-sm" required>
                            <option value="vat" {{ (old('tax_type', $invoice->tax_type ?? '') == 'vat') ? 'selected' : '' }}>VAT Registered (Regular)</option>
                            <option value="non_vat" {{ (old('tax_type', $invoice->tax_type ?? '') == 'non_vat') ? 'selected' : '' }}>Non-VAT</option>
                            <option value="vat_exempt" {{ (old('tax_type', $invoice->tax_type ?? '') == 'vat_exempt') ? 'selected' : '' }}>VAT Exempt</option>
                            <option value="zero_rated" {{ (old('tax_type', $invoice->tax_type ?? '') == 'zero_rated') ? 'selected' : '' }}>Zero Rated</option>
                        </select>
                    </div>
                     <div class="col-md-3">
                        <label class="form-label small text-muted">Payment Terms</label>
                        <select name="payment_terms" id="paymentTerms" class="form-select form-select-sm">
                            <option value="Due on Receipt" {{ (old('payment_terms', $invoice->payment_terms ?? '') == 'Due on Receipt') ? 'selected' : '' }}>Due on Receipt</option>
                            <option value="Net 15" {{ (old('payment_terms', $invoice->payment_terms ?? '') == 'Net 15') ? 'selected' : '' }}>Net 15 Days</option>
                            <option value="Net 30" {{ (old('payment_terms', $invoice->payment_terms ?? 'Net 30') == 'Net 30') ? 'selected' : '' }}>Net 30 Days</option>
                            <option value="Net 60" {{ (old('payment_terms', $invoice->payment_terms ?? '') == 'Net 60') ? 'selected' : '' }}>Net 60 Days</option>
                            <option value="COD" {{ (old('payment_terms', $invoice->payment_terms ?? '') == 'COD') ? 'selected' : '' }}>Cash on Delivery (COD)</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small text-muted">{{ $contactLabel }}</label>
                        <div class="input-group">
                            <select name="contact_id" id="contactSelect" class="form-select" required>
                                <option value="">Select {{ $contactLabel }}...</option>
                                @foreach($contacts as $contact)
                                    <option value="{{ $contact->id }}" {{ (old('contact_id', $invoice->contact_id ?? '') == $contact->id) ? 'selected' : '' }}>
                                        {{ $contact->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#quickAddContactModal">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Reference #</label>
                        <input type="text" name="reference" class="form-control" placeholder="PO-1234" value="{{ old('reference', $invoice->reference ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Date</label>
                        <input type="date" name="date" id="dateInput" class="form-control" value="{{ old('date', $defaultDate) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Due Date</label>
                        <input type="date" name="due_date" id="dueDateInput" class="form-control" value="{{ old('due_date', $defaultDueDate) }}" required>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                     <div class="col-md-12">
                        <label class="form-label small text-muted">Notes</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="1" placeholder="Internal notes...">{{ old('notes', $invoice->notes ?? '') }}</textarea>
                     </div>
                </div>
            </div>
        </div>

        {{-- Dynamic Items --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light py-2">
                <div class="row text-muted small fw-bold">
                    <div class="col-md-4">Account (GL)</div>
                    <div class="col-md-3">Description</div>
                    <div class="col-md-1 text-end">Qty</div>
                    <div class="col-md-2 text-end">Unit Price</div>
                    <div class="col-md-2 text-end">Amount</div>
                </div>
            </div>
            <div class="card-body p-0" id="lines-container">
                @php
                    // Retrieve items from validation error (old input) OR existing invoice items OR default 1 empty row
                    $existingItems = old('items', $isEdit && $invoice->items->count() > 0 ? $invoice->items : [0]);
                @endphp

                @foreach($existingItems as $index => $item)
                <div class="row g-2 p-2 border-bottom line-row align-items-center">
                    <div class="col-md-4">
                        <select name="items[{{ $index }}][account_id]" class="form-select form-select-sm" required>
                            <option value="">Select Account...</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ ($item['account_id'] ?? '') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->code }} - {{ $acc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="items[{{ $index }}][description]" class="form-control form-control-sm" placeholder="Description" value="{{ $item['description'] ?? '' }}" required>
                    </div>
                    <div class="col-md-1">
                        <input type="number" step="0.01" name="items[{{ $index }}][quantity]" class="form-control form-control-sm text-end qty-input" value="{{ $item['quantity'] ?? 1 }}" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="items[{{ $index }}][unit_price]" class="form-control form-control-sm text-end price-input" value="{{ $item['unit_price'] ?? '0.00' }}" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-center">
                        {{-- Amount is calculated via JS, we just pre-calculate it here for display --}}
                        @php $amt = ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0); @endphp
                        <input type="text" class="form-control form-control-sm text-end bg-light amount-display" value="{{ number_format($amt, 2, '.', '') }}" readonly disabled>
                        
                        <button type="button" class="btn btn-sm text-danger ms-2 remove-row" style="{{ count($existingItems) > 1 ? '' : 'display:none;' }}">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-light btn-sm text-primary" id="add-line">
                    <i class="bi bi-plus-circle"></i> Add Line
                </button>
                <div class="text-end">
                    <div class="small text-muted">Grand Total</div>
                    <h5 class="fw-bold text-dark m-0" id="grand-total">0.00</h5>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Quick Add Contact Modal --}}
<div class="modal fade" id="quickAddContactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">New {{ $contactLabel }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div id="quickAddError" class="alert alert-danger d-none"></div>
                <form id="quickAddContactForm">
                    @csrf <input type="hidden" name="type" value="{{ $contactType }}">
                    <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Tax Number *</label><input type="text" name="tax_number" class="form-control" required></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="saveContactBtn">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Line Item Logic
    // Initialize lineIndex based on existing rows
    let lineIndex = {{ count($existingItems) }};
    const container = document.getElementById('lines-container');

    function calculateRow(row) {
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const total = qty * price;
        row.querySelector('.amount-display').value = total.toFixed(2);
        return total;
    }

    function calculateTotal() {
        let grandTotal = 0;
        container.querySelectorAll('.line-row').forEach(row => grandTotal += calculateRow(row));
        document.getElementById('grand-total').textContent = grandTotal.toFixed(2);
    }
    
    // Initial Calc
    calculateTotal();

    container.addEventListener('input', e => {
        if (e.target.matches('.qty-input, .price-input')) calculateTotal();
    });

    document.getElementById('add-line').addEventListener('click', () => {
        // Clone the first row as a template
        const template = container.querySelector('.line-row').cloneNode(true);
        
        template.querySelectorAll('input, select').forEach(input => {
            // Update index
            input.name = input.name.replace(/\[\d+\]/, `[${lineIndex}]`);
            
            // Reset values
            if(input.classList.contains('qty-input')) input.value = "1";
            else if(input.classList.contains('price-input')) input.value = "0.00";
            else if(input.classList.contains('amount-display')) input.value = "0.00";
            else if(input.type === 'text' || input.tagName === 'SELECT') input.value = "";
        });
        
        template.querySelector('.remove-row').style.display = 'inline-block';
        container.appendChild(template);
        lineIndex++;
    });

    container.addEventListener('click', e => {
        if (e.target.closest('.remove-row')) {
            const rows = container.querySelectorAll('.line-row');
            if(rows.length > 1) {
                e.target.closest('.line-row').remove();
                calculateTotal();
            }
        }
    });

    // 2. Payment Terms Logic
    const termsSelect = document.getElementById('paymentTerms');
    const dateInput = document.getElementById('dateInput');
    const dueDateInput = document.getElementById('dueDateInput');

    function updateDueDate() {
        // Only auto-update if not editing, OR strictly based on terms change
        // For simplicity, we auto-update on term change.
        const term = termsSelect.value;
        const baseDate = new Date(dateInput.value);
        if (isNaN(baseDate)) return;

        let daysToAdd = 0;
        if (term === 'Net 15') daysToAdd = 15;
        else if (term === 'Net 30') daysToAdd = 30;
        else if (term === 'Net 60') daysToAdd = 60;
        
        baseDate.setDate(baseDate.getDate() + daysToAdd);
        dueDateInput.value = baseDate.toISOString().split('T')[0];
    }

    termsSelect.addEventListener('change', updateDueDate);
    dateInput.addEventListener('change', updateDueDate);
    
    // 3. Contact Modal Logic
    const saveContactBtn = document.getElementById('saveContactBtn');
    const contactForm = document.getElementById('quickAddContactForm');

    saveContactBtn.addEventListener('click', function() {
        const formData = new FormData(contactForm);
        const errorDiv = document.getElementById('quickAddError');
        
        saveContactBtn.disabled = true;
        saveContactBtn.textContent = 'Saving...';
        errorDiv.classList.add('d-none');

        fetch('{{ route("contacts.store") }}', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('quickAddContactModal'));
                modal.hide();
                contactForm.reset();

                const select = document.getElementById('contactSelect');
                const option = new Option(data.contact.name, data.contact.id);
                select.add(option);
                select.value = data.contact.id;
            } else {
                throw new Error(data.message || 'Validation failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorDiv.textContent = 'Failed to save contact. Please check required fields.';
            errorDiv.classList.remove('d-none');
        })
        .finally(() => {
            saveContactBtn.disabled = false;
            saveContactBtn.textContent = 'Save {{ $contactLabel }}';
        });
    });
});
</script>
@endsection
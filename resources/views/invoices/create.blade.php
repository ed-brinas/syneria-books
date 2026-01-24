@extends('layouts.app')

@php
    $label = ($type === 'invoice') ? 'Sales Invoice' : 'Purchase Bill';
    $isEdit = isset($invoice);
    $pageTitle = $isEdit ? 'Edit ' . $label : 'New ' . $label;
    $action = $isEdit ? route('invoices.update', $invoice->id) : route('invoices.store');
    
    $defaultDate = $isEdit ? $invoice->date->format('Y-m-d') : date('Y-m-d');
    $defaultDueDate = $isEdit ? $invoice->due_date->format('Y-m-d') : date('Y-m-d', strtotime('+30 days'));
@endphp

@section('title', $pageTitle)

@section('content')
<style>
    /* Custom styles for the file upload area */
    .upload-drop-zone {
        border: 2px dashed #dee2e6;
        transition: all 0.3s ease;
        background-color: #f8f9fa;
        cursor: pointer;
    }
    .upload-drop-zone:hover, .upload-drop-zone.dragover {
        border-color: #0d6efd;
        background-color: #e9ecef;
    }
    .upload-drop-zone input[type="file"] {
        cursor: pointer;
    }
</style>

<div class="container-fluid py-4">
    <form action="{{ $action }}" method="POST" id="invoiceForm" enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif
        
        <input type="hidden" name="type" value="{{ $type }}">
        <input type="hidden" name="subtype" value="standard">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 text-dark fw-bold">
                {{ $pageTitle }} 
                @if($isEdit && $invoice->number) <span class="text-muted ms-2">#{{ $invoice->number }}</span> @endif
            </h2>
            <div class="btn-group">
                <a href="{{ route('invoices.index', ['type' => $type]) }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                
                @if(!$isEdit || in_array($invoice->status, ['draft', 'review']))
                    <button type="submit" name="action" value="draft" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-save"></i> Save Draft
                    </button>
                    <button type="submit" name="action" value="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-send"></i> Submit for Review
                    </button>
                @endif
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul></div>
        @endif

        {{-- Header Info --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">{{ ($type === 'invoice') ? 'Invoice Number' : 'Bill Number' }}</label>
                        <input type="text" class="form-control bg-light" value="{{ $invoice->number ?? '(Generated on Post)' }}" disabled readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Reference / PO #</label>
                        <input type="text" name="reference" class="form-control" value="{{ old('reference', $invoice->reference ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ old('date', $defaultDate) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $defaultDueDate) }}" required>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Contact</label>
                        <select name="contact_id" id="contactSelect" class="form-select" required>
                            <option value="" data-email="">Select Contact...</option>
                            @foreach($contacts as $c)
                                <option value="{{ $c->id }}" 
                                    data-email="{{ $c->email }}"
                                    {{ (old('contact_id', $invoice->contact_id ?? '') == $c->id) ? 'selected' : '' }}>
                                    {{ $c->name }} {{ $c->company_name ? ' - ' . $c->company_name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Currency</label>
                        <select name="currency_code" class="form-select" required>
                            @foreach($currencies as $code => $name)
                                <option value="{{ $code }}" {{ (old('currency_code', $invoice->currency_code ?? 'USD') == $code) ? 'selected' : '' }}>
                                    {{ $code }} - {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Tax Type</label>
                        <select name="tax_type" class="form-select" required>
                            <option value="vat" {{ (old('tax_type', $invoice->tax_type ?? '') == 'vat') ? 'selected' : '' }}>VAT Registered</option>
                            <option value="non_vat" {{ (old('tax_type', $invoice->tax_type ?? '') == 'non_vat') ? 'selected' : '' }}>Non-VAT</option>
                            <option value="vat_exempt" {{ (old('tax_type', $invoice->tax_type ?? '') == 'vat_exempt') ? 'selected' : '' }}>VAT Exempt</option>
                            <option value="zero_rated" {{ (old('tax_type', $invoice->tax_type ?? '') == 'zero_rated') ? 'selected' : '' }}>Zero Rated</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Payment Terms</label>
                        <select name="payment_terms" id="paymentTerms" class="form-select">
                            <option value="Due on Receipt" {{ (old('payment_terms', $invoice->payment_terms ?? '') == 'Due on Receipt') ? 'selected' : '' }}>Due on Receipt</option>
                            <option value="Net 30" {{ (old('payment_terms', $invoice->payment_terms ?? '') == 'Net 30') ? 'selected' : '' }} {{ !isset($invoice) ? 'selected' : '' }}>Net 30 Days</option>
                            <option value="Net 60" {{ (old('payment_terms', $invoice->payment_terms ?? '') == 'Net 60') ? 'selected' : '' }}>Net 60 Days</option>
                            <option value="Net 90" {{ (old('payment_terms', $invoice->payment_terms ?? '') == 'Net 90') ? 'selected' : '' }}>Net 90 Days</option>
                        </select>
                    </div>
                </div>
                
                <div class="row g-3 mt-2">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Withholding Tax Rate (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="withholding_tax_rate" class="form-control" 
                                value="{{ old('withholding_tax_rate', $invoice->withholding_tax_rate ?? 0) }}" placeholder="0.00">
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="form-text small">E.g., 1%, 2%, 5% or 10%</div>
                    </div>
                </div>

                {{-- E-Invoice Option --}}
                <div class="row mt-2">
                    <div class="col-12">
                        <div class="form-check" id="emailOptionContainer" style="display: none;">
                            <input class="form-check-input" type="checkbox" name="send_email_copy" id="sendEmailCopy" value="1">
                            <label class="form-check-label small text-muted" for="sendEmailCopy">
                                Email a copy of this invoice to the contact?
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Recurring Invoice Options --}}
                <div class="mt-4 pt-3 border-top">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="recurringToggle" name="is_recurring" {{ old('is_recurring', $invoice->is_recurring ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold small text-dark" for="recurringToggle">Make this a Recurring Invoice</label>
                    </div>

                    <div class="row g-3 mt-1" id="recurringOptions" style="display: {{ old('is_recurring', $invoice->is_recurring ?? false) ? 'flex' : 'none' }}">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Repeat Every</label>
                            <input type="number" name="recurrence_interval" class="form-control" min="1" value="{{ old('recurrence_interval', $invoice->recurrence_interval ?? 1) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Interval</label>
                            <select name="recurrence_type" class="form-select">
                                <option value="weeks" {{ (old('recurrence_type', $invoice->recurrence_type ?? '') == 'weeks') ? 'selected' : '' }}>Weeks</option>
                                <option value="months" {{ (old('recurrence_type', $invoice->recurrence_type ?? '') == 'months') ? 'selected' : '' }}>Months</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">End Date (Optional)</label>
                            <input type="date" name="recurrence_end_date" class="form-control" value="{{ old('recurrence_end_date', isset($invoice->recurrence_end_date) ? $invoice->recurrence_end_date->format('Y-m-d') : '') }}">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <span class="small text-muted fst-italic">Invoice will auto-generate on schedule.</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Items Table (Strictly following create-template structure) --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light py-2">
                <div class="row text-muted small fw-bold align-items-center">
                    <div class="col-md-2">Account</div>
                    <div class="col-md-2">Description</div>
                    <div class="col-md-2">Tax Rate</div>
                    <div class="col-md-1">Qty</div>
                    <div class="col-md-1">Price</div>
                    <div class="col-md-1">Disc %</div>
                    <div class="col-md-1">Tax Amt</div>
                    <div class="col-md-1 text-end">Net Amt</div>
                    <div class="col-1"></div>
                </div>
            </div>
            
            <div class="card-body p-0" id="lines-container">
                @php 
                    $existingItems = old('items', $isEdit && $invoice->items->count() ? $invoice->items : [
                        ['account_id' => '', 'description' => '', 'tax_rate_id' => '', 'quantity' => 1, 'unit_price' => 0, 'discount_rate' => 0, 'tax_amount' => 0, 'amount' => 0]
                    ]); 
                @endphp

                @foreach($existingItems as $index => $item)
                <div class="row g-2 p-2 border-bottom line-row align-items-center">
                    
                    <div class="col-md-2">
                        <select name="items[{{ $index }}][account_id]" class="form-select form-select-sm" required>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ ($item['account_id'] ?? '') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->code }} - {{ $acc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <input type="text" name="items[{{ $index }}][description]" class="form-control form-control-sm" 
                               value="{{ $item['description'] ?? '' }}" required>
                    </div>

                    <div class="col-md-2">
                        <select name="items[{{ $index }}][tax_rate_id]" class="form-select form-select-sm tax-select" required>
                            <option value="" data-rate="0">No Tax (0%)</option>
                            @foreach($taxRates as $rate)
                                <option value="{{ $rate->id }}" data-rate="{{ $rate->rate }}" 
                                    {{ ($item['tax_rate_id'] ?? '') == $rate->id ? 'selected' : '' }}>
                                    {{ $rate->name }} ({{ $rate->display_rate }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-1">
                        <input type="number" step="0.01" name="items[{{ $index }}][quantity]" 
                               class="form-control form-control-sm qty text-end" value="{{ $item['quantity'] ?? 1 }}" required onfocus="this.select()">
                    </div>

                    <div class="col-md-1">
                        <input type="number" step="0.01" name="items[{{ $index }}][unit_price]" 
                               class="form-control form-control-sm price text-end" value="{{ $item['unit_price'] ?? 0 }}" required onfocus="this.select()">
                    </div>

                    <div class="col-md-1">
                        <input type="number" step="0.01" name="items[{{ $index }}][discount_rate]" 
                               class="form-control form-control-sm discount text-end" value="{{ $item['discount_rate'] ?? 0 }}" onfocus="this.select()">
                    </div>

                    <div class="col-md-1">
                        <input type="text" class="form-control form-control-sm bg-light text-muted line-tax text-end" 
                               value="{{ number_format($item['tax_amount'] ?? 0, 2) }}" readonly>
                    </div>

                    <div class="col-md-1">
                        <input type="text" class="form-control form-control-sm bg-light line-total text-end" 
                               value="{{ number_format($item['amount'] ?? 0, 2) }}" readonly>
                    </div>

                    <div class="col-1 text-center action-col">
                        <button type="button" class="btn btn-link text-danger btn-sm remove-row p-0">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
            
            <div class="card-footer bg-white p-0">
                
                <div class="p-2 border-bottom">
                    <button type="button" class="btn btn-light btn-sm text-primary" id="add-line">
                        <i class="bi bi-plus-circle"></i> Add Line (+)
                    </button>
                </div>

                {{-- Totals Section (Matching Template Layout) --}}
                <div class="row g-2 p-2 align-items-center justify-content-end">
                    <div class="col-md-2 text-end text-muted small">Subtotal (Net)</div>
                    <div class="col-md-2 text-end text-muted small pe-4" id="subtotal-display">0.00</div>
                    <div class="col-1"></div>
                </div>
                
                <div class="row g-2 p-2 align-items-center justify-content-end mt-n3">
                    <div class="col-md-2 text-end text-muted small">Tax Total</div>
                    <div class="col-md-2 text-end text-muted small pe-4" id="tax-display">0.00</div>
                    <div class="col-1"></div>
                </div>

                <div class="row g-2 p-2 align-items-center justify-content-end border-top bg-light">
                    <div class="col-md-2 text-end fw-bold text-dark">Grand Total</div>
                    <div class="col-md-2 text-end fw-bold text-dark pe-4" id="grand-total">0.00</div>
                    <div class="col-1"></div>
                </div>

            </div>
        </div>
        
        {{-- Notes & Attachments --}}
        <div class="row mt-4">
            <div class="col-md-6">
                <label class="form-label small text-muted">Notes</label>
                <textarea name="notes" class="form-control" rows="5" placeholder="Enter notes here...">{{ old('notes', $invoice->notes ?? '') }}</textarea>
            </div>
            
            <div class="col-md-6">
                 <label class="form-label small text-muted mb-2"><i class="bi bi-paperclip"></i> Attachments</label>
                 
                 <!-- Custom Drag & Drop UI -->
                 <div class="upload-drop-zone rounded-3 p-4 text-center position-relative" id="drop-area">
                     <input type="file" name="attachments[]" id="file-input" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" multiple accept=".pdf,.png,.jpg,.jpeg">
                     
                     <div class="py-2">
                        <div class="mb-2">
                            <i class="bi bi-cloud-arrow-up text-primary" style="font-size: 2rem;"></i>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark">Click to upload or drag and drop</h6>
                        <p class="small text-muted mb-0">PDF, PNG, JPG (max 10MB)</p>
                     </div>
                 </div>

                 <!-- New File Preview Container -->
                 <div id="new-files-preview" class="mt-2 list-group list-group-flush small"></div>

                 <!-- Existing Files -->
                 @if($isEdit && $invoice->attachments->count() > 0)
                    <div class="mt-3">
                        <div class="small fw-bold text-muted mb-1">Previously Attached:</div>
                        <ul class="list-group list-group-flush border rounded-2 bg-white small">
                            @foreach($invoice->attachments as $att)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                                    <div class="d-flex align-items-center text-truncate">
                                        <i class="bi bi-file-earmark-text text-secondary me-2"></i> 
                                        <span>{{ $att->file_name }}</span>
                                    </div>
                                    {{-- Optional: Add remove link here if functionality exists --}}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </form>
</div>

{{-- Generic Error Modal --}}
<div class="modal fade" id="errorModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="errorModalBody">
                <p class="mb-0">An error occurred.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let lineIndex = {{ count($existingItems) }};
    const container = document.getElementById('lines-container');
    
    // Totals Elements
    const subtotalEl = document.getElementById('subtotal-display');
    const taxEl = document.getElementById('tax-display');
    const grandTotalEl = document.getElementById('grand-total');
    
    // Recurring Logic
    const recurringToggle = document.getElementById('recurringToggle');
    const recurringOptions = document.getElementById('recurringOptions');
    
    if(recurringToggle) {
        recurringToggle.addEventListener('change', function() {
            recurringOptions.style.display = this.checked ? 'flex' : 'none';
        });
    }

    // Contact Email Logic
    const contactSelect = document.getElementById('contactSelect');
    const emailContainer = document.getElementById('emailOptionContainer');
    
    function checkContactEmail() {
        const option = contactSelect.options[contactSelect.selectedIndex];
        const hasEmail = option && option.getAttribute('data-email');
        emailContainer.style.display = hasEmail ? 'block' : 'none';
    }
    
    if(contactSelect) {
        contactSelect.addEventListener('change', checkContactEmail);
        checkContactEmail(); // Initial check
    }

    // Error Modal
    const errorModalEl = document.getElementById('errorModal');
    const errorModalBody = document.getElementById('errorModalBody');
    const errorModal = new bootstrap.Modal(errorModalEl);

    // --- File Upload Logic ---
    const fileInput = document.getElementById('file-input');
    const dropArea = document.getElementById('drop-area');
    const previewList = document.getElementById('new-files-preview');

    if(fileInput && dropArea) {
        // Drag Effects
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropArea.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropArea.classList.remove('dragover');
            }, false);
        });

        // File Selection/Drop
        fileInput.addEventListener('change', handleFiles);
        dropArea.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            fileInput.files = dt.files;
            handleFiles();
        });

        function handleFiles() {
            previewList.innerHTML = ''; // Clear preview
            const files = Array.from(fileInput.files);
            
            if (files.length > 0) {
                files.forEach(file => {
                    // Format size
                    const size = file.size > 1024 * 1024 
                        ? (file.size / (1024 * 1024)).toFixed(2) + ' MB' 
                        : (file.size / 1024).toFixed(2) + ' KB';

                    const item = document.createElement('div');
                    item.className = 'list-group-item bg-transparent border-0 px-2 py-1 d-flex justify-content-between align-items-center text-muted';
                    item.innerHTML = `
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-success me-2"></i> 
                            <span class="text-dark">${file.name}</span>
                        </div>
                        <span class="small badge bg-light text-secondary border">${size}</span>
                    `;
                    previewList.appendChild(item);
                });
            }
        }
    }
    // --- End File Upload Logic ---

    function showError(message) {
        errorModalBody.innerHTML = `<p class="mb-0">${message}</p>`;
        errorModal.show();
    }

    function updateRemoveButtons() {
        const rows = container.querySelectorAll('.line-row');
        const shouldShow = rows.length > 1;
        rows.forEach(row => {
            const btn = row.querySelector('.remove-row');
            if (btn) {
                btn.style.display = shouldShow ? 'inline-block' : 'none';
            }
        });
    }

    // 1. Calculate Totals (Invoice Logic)
    function calculateTotals() {
        let subtotal = 0;
        let totalTax = 0;

        document.querySelectorAll('.line-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.qty').value) || 0;
            const price = parseFloat(row.querySelector('.price').value) || 0;
            const discountRate = parseFloat(row.querySelector('.discount').value) || 0;
            const taxSelect = row.querySelector('.tax-select');
            
            // Get tax rate from data attribute
            const taxRate = parseFloat(taxSelect.options[taxSelect.selectedIndex].dataset.rate) || 0;

            // Math: Net = (Qty * Price) * (1 - Discount/100)
            const grossAmount = qty * price;
            const discountFactor = (1 - (discountRate / 100));
            const netAmount = grossAmount * discountFactor;
            const lineTax = netAmount * taxRate;
            
            // Update UI fields
            row.querySelector('.line-total').value = netAmount.toFixed(2);
            row.querySelector('.line-tax').value = lineTax.toFixed(2);
            
            subtotal += netAmount;
            totalTax += lineTax;
        });

        const grandTotal = subtotal + totalTax;

        subtotalEl.textContent = subtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        taxEl.textContent = totalTax.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        grandTotalEl.textContent = grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    // Event Listeners (Delegated)
    container.addEventListener('input', function(e) {
        if (e.target.matches('.qty, .price, .discount, .tax-select')) {
            calculateTotals();
        }
    });

    container.addEventListener('change', function(e) {
        if (e.target.matches('.tax-select')) {
            calculateTotals();
        }
    });

    // Initial Runs
    calculateTotals();
    updateRemoveButtons();

    // 2. Add Line (Clone Node Method from Template)
    document.getElementById('add-line').addEventListener('click', function() {
        // Clone the first row
        const template = container.querySelector('.line-row').cloneNode(true);
        
        // Update names and reset values
        template.querySelectorAll('input, select').forEach(input => {
            // Replace array index in name attribute: items[0][qty] -> items[5][qty]
            input.name = input.name.replace(/\[\d+\]/, `[${lineIndex}]`);
            
            // Reset values based on type
            if(input.tagName === 'INPUT') {
                if(input.classList.contains('qty')) input.value = '1';
                else if(input.classList.contains('price')) input.value = '0';
                else if(input.classList.contains('discount')) input.value = '0';
                else if(input.classList.contains('line-tax') || input.classList.contains('line-total')) input.value = '0.00';
                else input.value = ''; // Description
            }
            if(input.tagName === 'SELECT') {
                input.selectedIndex = 0; // Reset dropdowns
            }
        });

        // Ensure button visibility (using class .remove-row to match template)
        const newBtn = template.querySelector('.remove-row');
        if(newBtn) newBtn.style.display = 'inline-block';

        container.appendChild(template);
        lineIndex++;
        
        updateRemoveButtons(); 
        calculateTotals();
    });

    // 3. Remove Line (Delegated)
    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row')) {
            const row = e.target.closest('.line-row');
            if (document.querySelectorAll('.line-row').length > 1) {
                row.remove();
                calculateTotals();
                updateRemoveButtons();
            } else {
                showError('An invoice must have at least one line item.');
            }
        }
    });
});
</script>
@endsection
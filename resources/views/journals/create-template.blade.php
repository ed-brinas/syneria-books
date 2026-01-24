@extends('layouts.app')

@section('title', 'New Journal Entry - SyneriaBooks')

@section('content')
<div class="container-fluid py-4">
    <form action="{{ isset($journal) ? route('journals.update', $journal->id) : route('journals.store') }}" method="POST" id="journalForm">
        @csrf
        @if(isset($journal))
            @method('PUT')
        @endif
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 text-dark fw-bold">
                @if(isset($journal))
                    Edit Journal Entry <span class="text-muted small">#{{ ucfirst($journal->status) }}</span>
                @elseif(isset($prefill) && !old('lines')) 
                    {{-- Only show "Reversal" title if not re-rendering after error --}}
                    New Reversal Entry
                @else
                    New Journal Entry
                @endif
            </h2>
            <div class="btn-group">
                <a href="{{ route('journals.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                
                <button type="submit" name="action" value="save" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-earmark"></i> 
                    @if(isset($journal) && $journal->status == 'review') Update Review @else Save Draft @endif
                </button>
                
                @if(!isset($journal) || $journal->status === 'draft')
                <button type="submit" name="action" value="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-arrow-right-circle"></i> Submit for Review
                </button>
                @endif
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        @endif

        {{-- Header Information --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Reference</label>
                        <input type="text" class="form-control bg-light" value="{{ $prefill['reference'] ?? '(Generated on Approval)' }}" disabled readonly>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ old('date', $prefill['date'] ?? date('Y-m-d')) }}" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Auto-reversing Date <i class="bi bi-info-circle" title="Optional: Date to auto-reverse this entry"></i></label>
                        <input type="date" name="auto_reverse_date" class="form-control" value="{{ old('auto_reverse_date', $prefill['auto_reverse_date'] ?? '') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small text-muted">Amounts are</label>
                        <select name="tax_type" id="tax_type" class="form-select">
                            @php 
                                $currentTax = old('tax_type', $prefill['tax_type'] ?? 'no_tax'); 
                            @endphp
                            <option value="no_tax" {{ $currentTax == 'no_tax' ? 'selected' : '' }}>No Tax</option>
                            <option value="exclusive" {{ $currentTax == 'exclusive' ? 'selected' : '' }}>Tax Exclusive</option>
                            <option value="inclusive" {{ $currentTax == 'inclusive' ? 'selected' : '' }}>Tax Inclusive</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small text-muted">Description</label>
                        <input type="text" name="description" class="form-control" 
                               value="{{ old('description', $prefill['description'] ?? '') }}" 
                               placeholder="Summary of transaction" required>
                    </div>
                </div>
            </div>
        </div>

        {{-- Journal Lines --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light py-2">
                <div class="row text-muted small fw-bold align-items-center">
                    <div class="col-md-2">Account</div>
                    <div class="col-md-3">Description</div>
                    <div class="col-md-2">Tax Rate</div>
                    <div class="col-md-2 text-end">Debit</div>
                    <div class="col-md-2 text-end">Credit</div>
                    <div class="col-1"></div>
                </div>
            </div>
            
            <div class="card-body p-0" id="lines-container">
                @php
                    $linesToRender = old('lines', $prefill['lines'] ?? [ 
                        ['account_id' => '', 'description' => '', 'debit' => 0, 'credit' => 0, 'tax_code_id' => '', 'tax_amount' => 0],
                        ['account_id' => '', 'description' => '', 'debit' => 0, 'credit' => 0, 'tax_code_id' => '', 'tax_amount' => 0]
                    ]);
                @endphp

                @foreach($linesToRender as $index => $line)
                <div class="row g-2 p-2 border-bottom line-row align-items-center">
                    
                    <div class="col-md-3">
                        <select name="lines[{{ $index }}][account_id]" class="form-select form-select-sm" required>
                            <option value="">Select Account...</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" 
                                    {{-- FIX: Check against array key --}}
                                    {{ ($line['account_id'] ?? '') == $account->id ? 'selected' : '' }}>
                                    {{ $account->code }} - {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <input type="text" name="lines[{{ $index }}][description]" class="form-control form-control-sm" 
                               value="{{ $line['description'] ?? '' }}" placeholder="Line detail">
                    </div>              

                    <div class="col-md-2">
                        <select name="lines[{{ $index }}][tax_code_id]" class="form-select form-select-sm tax-select" data-rate="0">
                            <option value="" data-rate="0">None (0%)</option>
                            @foreach($taxRates as $rate)
                                <option value="{{ $rate->id }}" data-rate="{{ $rate->rate }}" 
                                    {{ ($line['tax_code_id'] ?? '') == $rate->id ? 'selected' : '' }}>
                                    {{ $rate->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="lines[{{ $index }}][tax_amount]" class="tax-amount-input" value="{{ $line['tax_amount'] ?? 0 }}">
                    </div>

                    <div class="col-md-2">
                        <input type="number" step="0.01" name="lines[{{ $index }}][debit]" 
                               class="form-control form-control-sm text-end debit-input" 
                               value="{{ $line['debit'] ?? 0 }}" onfocus="this.select()">
                    </div>

                    <div class="col-md-2">
                        <input type="number" step="0.01" name="lines[{{ $index }}][credit]" 
                               class="form-control form-control-sm text-end credit-input" 
                               value="{{ $line['credit'] ?? 0 }}" onfocus="this.select()">
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

                <div class="row g-2 p-2 align-items-center">
                    <div class="col-md-7 text-end text-muted small">Subtotal</div>
                    <div class="col-md-2 text-end text-muted small pe-4" id="display-subtotal-debit">0.00</div>
                    <div class="col-md-2 text-end text-muted small pe-4" id="display-subtotal-credit">0.00</div>
                    <div class="col-1"></div>
                </div>
                
                <div class="row g-2 p-2 align-items-center mt-n3">
                    <div class="col-md-7 text-end text-muted small">Tax Total</div>
                    <div class="col-md-2 text-end text-muted small pe-4" id="display-tax-debit">0.00</div>
                    <div class="col-md-2 text-end text-muted small pe-4" id="display-tax-credit">0.00</div>
                    <div class="col-1"></div>
                </div>

                <div class="row g-2 p-2 align-items-center border-top bg-light">
                    <div class="col-md-7 text-end fw-bold text-dark">Total</div>
                    <div class="col-md-2 text-end fw-bold text-dark pe-4" id="total-debit">0.00</div>
                    <div class="col-md-2 text-end fw-bold text-dark pe-4" id="total-credit">0.00</div>
                    <div class="col-1"></div>
                </div>

            </div>
        </div>
    </form>
</div>

{{-- Validation Error Modal --}}
<div class="modal fade" id="validationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body py-4 text-center">
                <i class="bi bi-exclamation-triangle-fill text-warning fs-1 mb-2"></i>
                <h5 class="fw-bold">Out of Balance</h5>
                <p class="text-muted" id="validationMessage">Total Debits must equal Total Credits.</p>
                <button type="button" class="btn btn-secondary mt-2" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let lineIndex = {{ count($linesToRender) }};
    const container = document.getElementById('lines-container');
    const taxTypeSelect = document.getElementById('tax_type');

    function updateRemoveButtons() {
        const rows = container.querySelectorAll('.line-row');
        const shouldShow = rows.length >= 3;
        rows.forEach(row => {
            const btn = row.querySelector('.remove-row');
            if (btn) {
                btn.style.display = shouldShow ? 'inline-block' : 'none';
            }
        });
    }

    function calculateTotals() {
        let subtotalDebit = 0, subtotalCredit = 0;
        let taxTotalDebit = 0, taxTotalCredit = 0;
        let grandTotalDebit = 0, grandTotalCredit = 0;
        
        const taxType = taxTypeSelect.value; 

        document.querySelectorAll('.line-row').forEach(row => {
            const debitInput = row.querySelector('.debit-input');
            const creditInput = row.querySelector('.credit-input');
            const taxSelect = row.querySelector('.tax-select');
            const taxAmountInput = row.querySelector('.tax-amount-input');

            let debit = parseFloat(debitInput.value) || 0;
            let credit = parseFloat(creditInput.value) || 0;
            let taxRate = parseFloat(taxSelect.options[taxSelect.selectedIndex].dataset.rate) || 0;
            
            taxSelect.disabled = (taxType === 'no_tax');
            if (taxType === 'no_tax') taxRate = 0;

            let taxAmount = 0;
            let netAmount = 0; 

            if (debit > 0) {
                if (taxType === 'exclusive') {
                    netAmount = debit;
                    taxAmount = debit * taxRate;
                } else if (taxType === 'inclusive') {
                    netAmount = debit / (1 + taxRate);
                    taxAmount = debit - netAmount;
                } else {
                    netAmount = debit;
                    taxAmount = 0;
                }
                
                subtotalDebit += netAmount;
                taxTotalDebit += taxAmount;
                grandTotalDebit += (netAmount + taxAmount);
                taxAmountInput.value = taxAmount.toFixed(2);
            }

            if (credit > 0) {
                if (taxType === 'exclusive') {
                    netAmount = credit;
                    taxAmount = credit * taxRate;
                } else if (taxType === 'inclusive') {
                    netAmount = credit / (1 + taxRate);
                    taxAmount = credit - netAmount;
                } else {
                    netAmount = credit;
                    taxAmount = 0;
                }

                subtotalCredit += netAmount;
                taxTotalCredit += taxAmount;
                grandTotalCredit += (netAmount + taxAmount);
                taxAmountInput.value = taxAmount.toFixed(2);
            }
        });

        document.getElementById('display-subtotal-debit').textContent = subtotalDebit.toFixed(2);
        document.getElementById('display-subtotal-credit').textContent = subtotalCredit.toFixed(2);
        
        document.getElementById('display-tax-debit').textContent = taxTotalDebit.toFixed(2);
        document.getElementById('display-tax-credit').textContent = taxTotalCredit.toFixed(2);
        
        document.getElementById('total-debit').textContent = grandTotalDebit.toFixed(2);
        document.getElementById('total-credit').textContent = grandTotalCredit.toFixed(2);

        const isBalanced = Math.abs(grandTotalDebit - grandTotalCredit) < 0.01;
        const statusClass = isBalanced 
            ? 'col-md-2 text-end fw-bold text-success pe-4' 
            : 'col-md-2 text-end fw-bold text-danger pe-4';

        document.getElementById('total-debit').className = statusClass;
        document.getElementById('total-credit').className = statusClass;
    }

    // Event Listeners
    container.addEventListener('input', calculateTotals);
    container.addEventListener('change', calculateTotals); 
    taxTypeSelect.addEventListener('change', calculateTotals);

    // Initial runs
    calculateTotals();
    updateRemoveButtons(); // Run on load to hide buttons if starting with 2 rows

    // Add Line
    document.getElementById('add-line').addEventListener('click', function() {
        const template = container.querySelector('.line-row').cloneNode(true);
        
        template.querySelectorAll('input, select').forEach(input => {
            input.name = input.name.replace(/\[\d+\]/, `[${lineIndex}]`);
            if(input.tagName === 'INPUT') input.value = (input.type === 'number') ? '0.00' : '';
            if(input.tagName === 'SELECT') input.selectedIndex = 0;
        });

        // Ensure the button is visible on the new row (in case it was cloned from a hidden state)
        // Though updateRemoveButtons will handle it, it's good practice.
        const newBtn = template.querySelector('.remove-row');
        if(newBtn) newBtn.style.display = 'inline-block';

        container.appendChild(template);
        lineIndex++;
        
        updateRemoveButtons(); // Update visibility after adding
    });

    // Remove Line
    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row')) {
            const row = e.target.closest('.line-row');
            if (document.querySelectorAll('.line-row').length > 2) {
                row.remove();
                calculateTotals();
                updateRemoveButtons(); // Update visibility after removing
            }
        }
    });

    // Submit Validation
    document.getElementById('journalForm').addEventListener('submit', function(e) {
        const d = parseFloat(document.getElementById('total-debit').textContent);
        const c = parseFloat(document.getElementById('total-credit').textContent);
        
        if (Math.abs(d - c) > 0.01) {
            e.preventDefault();
            new bootstrap.Modal(document.getElementById('validationModal')).show();
        }
    });
});
</script>
@endsection
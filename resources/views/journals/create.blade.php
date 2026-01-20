@extends('layouts.app')

@section('title', 'New Journal Entry - SyneriaBooks')

@section('content')
<div class="container-fluid py-4">
    {{-- Dynamic Action: Store (New) vs Update (Edit) --}}
    <form action="{{ isset($journal) ? route('journals.update', $journal->id) : route('journals.store') }}" method="POST" id="journalForm">
        @csrf
        @if(isset($journal))
            @method('PUT')
        @endif
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 text-dark fw-bold">
                @if(isset($journal))
                    Edit Journal Entry <span class="text-muted small">#Draft</span>
                @elseif(isset($prefill))
                    New Reversal Entry
                @else
                    New Journal Entry
                @endif
            </h2>
            <div class="btn-group">
                <a href="{{ route('journals.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                
                {{-- Option 1: Save as Draft --}}
                <button type="submit" name="status" value="draft" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-earmark"></i> {{ isset($journal) ? 'Update Draft' : 'Save as Draft' }}
                </button>
                
                {{-- Option 2: Post --}}
                <button type="submit" name="status" value="posted" class="btn btn-primary btn-sm">
                    <i class="bi bi-check-lg"></i> Post Journal
                </button>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Header Information --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Reference</label>
                        <input type="text" class="form-control bg-light" value="{{ $prefill['reference'] ?? '(Auto-Generated)' }}" disabled readonly>
                        <div class="form-text small"><i class="bi bi-lock-fill"></i> System assigned on Post</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ $prefill['date'] ?? date('Y-m-d') }}" required>
                    </div>                    
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Description</label>
                        <input type="text" name="description" class="form-control" 
                               value="{{ $prefill['description'] ?? '' }}" 
                               placeholder="Summary of transaction" required>
                    </div>
                </div>
            </div>
        </div>

        {{-- Journal Lines --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light py-2">
                <div class="row text-muted small fw-bold">
                    <div class="col-md-4">Account</div>
                    {{-- Grid adjusted for action button alignment --}}
                    <div class="col-md-3">Description</div>
                    <div class="col-md-2 text-end">Debit</div>
                    <div class="col-md-2 text-end">Credit</div>
                    <div class="col-md-1 text-center"></div>
                </div>
            </div>
            <div class="card-body p-0" id="lines-container">
                
                @php
                    // Use prefilled lines (for Reversals/Edits) or default 2 empty lines
                    $linesToRender = isset($prefill['lines']) ? $prefill['lines'] : [ 
                        ['account_id' => '', 'description' => '', 'debit' => 0, 'credit' => 0],
                        ['account_id' => '', 'description' => '', 'debit' => 0, 'credit' => 0]
                    ];
                @endphp

                @foreach($linesToRender as $index => $line)
                <div class="row g-2 p-2 border-bottom line-row align-items-center">
                    <div class="col-md-4">
                        <select name="lines[{{ $index }}][account_id]" class="form-select form-select-sm" required>
                            <option value="">Select Account...</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" {{ $line['account_id'] == $account->id ? 'selected' : '' }}>
                                    {{ $account->code }} - {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="lines[{{ $index }}][description]" class="form-control form-control-sm" 
                               value="{{ $line['description'] }}" placeholder="Line detail">
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="lines[{{ $index }}][debit]" 
                               class="form-control form-control-sm text-end debit-input" 
                               value="{{ $line['debit'] }}" onfocus="this.select()">
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="lines[{{ $index }}][credit]" 
                               class="form-control form-control-sm text-end credit-input" 
                               value="{{ $line['credit'] }}" onfocus="this.select()">
                    </div>
                    {{-- Action Column: Vertically aligned delete button --}}
                    <div class="col-md-1 text-center action-col">
                        @if($index >= 2 || count($linesToRender) > 2)
                             <button type="button" class="btn btn-danger btn-sm remove-row" title="Remove line">
                                <i class="bi bi-x-lg"></i>
                             </button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-light btn-sm text-primary" id="add-line">
                    <i class="bi bi-plus-circle"></i> Add Line
                </button>
                <div class="text-end">
                    <div class="small text-muted">Total Debit / Credit</div>
                    <span class="fw-bold text-dark me-3" id="total-debit">0.00</span>
                    <span class="fw-bold text-dark" id="total-credit">0.00</span>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Validation Error Modal --}}
<div class="modal fade" id="validationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-danger fw-bold">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Validation Error
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p id="validationMessage" class="mb-0 fs-6">Journal Entry must be balanced.</p>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Okay, I'll fix it</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let lineIndex = {{ count($linesToRender) }};

    function updateTotals() {
        let totalDebit = 0;
        let totalCredit = 0;

        document.querySelectorAll('.debit-input').forEach(input => {
            totalDebit += parseFloat(input.value) || 0;
        });
        document.querySelectorAll('.credit-input').forEach(input => {
            totalCredit += parseFloat(input.value) || 0;
        });

        document.getElementById('total-debit').textContent = totalDebit.toFixed(2);
        document.getElementById('total-credit').textContent = totalCredit.toFixed(2);
        
        const totalsMatch = Math.abs(totalDebit - totalCredit) < 0.01;
        document.getElementById('total-credit').className = totalsMatch ? 'fw-bold text-success' : 'fw-bold text-danger';
    }
    
    // Calculate on load for reversals
    updateTotals();

    document.getElementById('lines-container').addEventListener('input', function(e) {
        if (e.target.classList.contains('debit-input') || e.target.classList.contains('credit-input')) {
            updateTotals();
        }
    });

    document.getElementById('add-line').addEventListener('click', function() {
        const container = document.getElementById('lines-container');
        // Clone the first row found
        const template = container.querySelector('.line-row').cloneNode(true);
        
        template.querySelectorAll('input').forEach(input => {
            input.name = input.name.replace(/\[\d+\]/, `[${lineIndex}]`);
            input.value = input.type === 'number' ? '0.00' : '';
        });
        
        // Fix select name and reset
        const select = template.querySelector('select');
        select.name = select.name.replace(/\[\d+\]/, `[${lineIndex}]`);
        select.selectedIndex = 0;

        const actionCol = template.querySelector('.action-col');
        actionCol.innerHTML = ''; 
        
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-danger btn-sm remove-row';
        btn.innerHTML = '<i class="bi bi-x-lg"></i>';
        btn.title = "Remove line";
        actionCol.appendChild(btn);

        container.appendChild(template);
        lineIndex++;
    });

    document.getElementById('lines-container').addEventListener('click', function(e) {
        // Fix: Use closest() to handle clicks on the icon inside the button
        const btn = e.target.closest('.remove-row');
        if (btn) {
            btn.closest('.line-row').remove();
            updateTotals();
        }
    });

    document.getElementById('journalForm').addEventListener('submit', function(e) {
        const d = parseFloat(document.getElementById('total-debit').textContent);
        const c = parseFloat(document.getElementById('total-credit').textContent);
        
        // Balance check
        if (Math.abs(d - c) > 0.01) {
            e.preventDefault();
            // Replace alert with Bootstrap Modal
            const modalEl = document.getElementById('validationModal');
            const msgEl = document.getElementById('validationMessage');
            msgEl.textContent = 'Journal Entry is not balanced. Total Debits must equal Total Credits before posting.';
            
            new bootstrap.Modal(modalEl).show();
        }
    });
});
</script>
@endsection
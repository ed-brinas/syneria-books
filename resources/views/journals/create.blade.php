@extends('layouts.app')

@section('title', 'New Journal Entry - SyneriaBooks')

@section('content')
<div class="container-fluid py-4">
    <form action="{{ route('journals.store') }}" method="POST" id="journalForm">
        @csrf
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 text-dark fw-bold">New Journal Entry</h2>
            <div>
                <a href="{{ route('journals.index') }}" class="btn btn-outline-secondary btn-sm me-2">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm">Post Journal</button>
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
                        <input type="text" class="form-control bg-light" value="(Auto-Generated)" disabled readonly>
                        <div class="form-text small"><i class="bi bi-lock-fill"></i> System assigned on save</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>                    
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="Summary of transaction" required>
                    </div>
                </div>
            </div>
        </div>

        {{-- Journal Lines --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light py-2">
                <div class="row text-muted small fw-bold">
                    <div class="col-md-4">Account</div>
                    {{-- Reduced Description width to make room for actions --}}
                    <div class="col-md-3">Description</div>
                    <div class="col-md-2 text-end">Debit</div>
                    <div class="col-md-2 text-end">Credit</div>
                    <div class="col-md-1 text-center"></div> {{-- Action Column --}}
                </div>
            </div>
            <div class="card-body p-0" id="lines-container">
                {{-- Initial Lines (Minimum 2 required for double entry) --}}
                @foreach([0, 1] as $index)
                <div class="row g-2 p-2 border-bottom line-row align-items-center">
                    <div class="col-md-4">
                        <select name="lines[{{ $index }}][account_id]" class="form-select form-select-sm" required>
                            <option value="">Select Account...</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="lines[{{ $index }}][description]" class="form-control form-control-sm" placeholder="Line detail">
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="lines[{{ $index }}][debit]" class="form-control form-control-sm text-end debit-input" value="0.00" onfocus="this.select()">
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="lines[{{ $index }}][credit]" class="form-control form-control-sm text-end credit-input" value="0.00" onfocus="this.select()">
                    </div>
                    {{-- Dedicated Action Column --}}
                    <div class="col-md-1 text-center action-col">
                        {{-- Only show remove button for dynamically added lines (handled via JS for new lines) --}}
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    let lineIndex = 2;

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

    document.getElementById('lines-container').addEventListener('input', function(e) {
        if (e.target.classList.contains('debit-input') || e.target.classList.contains('credit-input')) {
            updateTotals();
        }
    });

    document.getElementById('add-line').addEventListener('click', function() {
        const container = document.getElementById('lines-container');
        // Clone the first row as a template
        const template = container.firstElementChild.cloneNode(true);
        
        // Reset Inputs
        template.querySelectorAll('input').forEach(input => {
            input.name = input.name.replace(/\[\d+\]/, `[${lineIndex}]`);
            input.value = input.type === 'number' ? '0.00' : '';
        });
        template.querySelector('select').name = `lines[${lineIndex}][account_id]`;
        template.querySelector('select').selectedIndex = 0;

        // Handle Delete Button in the dedicated action column
        const actionCol = template.querySelector('.action-col');
        // Ensure strictly one button exists
        actionCol.innerHTML = ''; 
        
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-link text-danger p-0 text-decoration-none remove-row fw-bold fs-5';
        btn.innerHTML = '&times;'; // The "x" symbol
        btn.title = "Remove line";
        
        actionCol.appendChild(btn);

        container.appendChild(template);
        lineIndex++;
    });

    // Event delegation for Remove Row
    document.getElementById('lines-container').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            // Remove the specific line row
            e.target.closest('.line-row').remove();
            updateTotals();
        }
    });

    document.getElementById('journalForm').addEventListener('submit', function(e) {
        const d = parseFloat(document.getElementById('total-debit').textContent);
        const c = parseFloat(document.getElementById('total-credit').textContent);
        if (Math.abs(d - c) > 0.01) {
            e.preventDefault();
            alert('Journal Entry must be balanced (Debits = Credits) before posting.');
        }
    });
});
</script>
@endsection
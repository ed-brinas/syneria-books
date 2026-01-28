<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal Voucher {{ $journal->reference }}</title>
    
    <!-- Using Bootstrap CSS for layout consistency in View mode -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .paper {
            background: white;
            max-width: 210mm;
            margin: 0 auto;
            padding: 10mm;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            border-radius: 4px;
        }
        .table-custom th { background-color: #f8f9fa; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.5px; }
        .stamp {
            position: absolute; top: 20px; right: 20px;
            font-size: 2rem; font-weight: bold; opacity: 0.2;
            transform: rotate(-15deg); border: 3px solid currentColor; padding: 5px 15px;
        }
        .stamp.draft { color: gray; }
        .stamp.review { color: orange; }
        .stamp.approved { color: blue; }
        .stamp.posted { color: green; }
        .stamp.voided { color: red; }
        
        @media print {
            body { background: white; padding: 0; }
            .paper { box-shadow: none; max-width: 100%; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <!-- Toolbar -->
     <div class="container mb-4 no-print">
        <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border">
            <a href="{{ route('journals.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to List</a>
            <div>
                {{-- Dynamic Role Checks would go here based on controller logic --}}
                <button onclick="window.print()" class="btn btn-dark btn-sm"><i class="bi bi-printer"></i> Print / PDF</button>
            </div>
        </div>
    </div>

    <!-- The Document -->
    <div class="paper position-relative">
        <div class="stamp {{ $journal->status }}">{{ strtoupper($journal->status) }}</div>

        <!-- Header -->
        <div class="row mb-5">
            <div class="col-7">
                {{-- Show Specific Branch Name (BIR Compliance) --}}
                <h4 class="fw-bold">{{ $journal->branch->name ?? auth()->user()->tenant->company_name }}</h4>
                <div class="text-muted small">
                    {{ $journal->branch->address ?? 'Branch Address' }}<br>
                    {{ $journal->branch->city ?? '' }} {{ $journal->branch->zip_code ?? '' }}<br>
                    TIN: {{ $journal->branch->tin ?? '---' }}
                </div>
            </div>
            <div class="col-5 text-end">
                <h3 class="fw-bold mb-3">JOURNAL VOUCHER</h3>
                <h5 class="fw-bold text-primary">{{ $journal->reference ?? 'DRAFT' }}</h5>
                <table class="table table-borderless table-sm w-auto ms-auto text-end">
                    <tr>
                        <td class="text-muted">Date:</td>
                        <td class="fw-bold">{{ $journal->date->format('d M Y') }}</td></tr>
                    <tr>
                        <td class="text-muted">Branch Code:</td>
                        <td>{{ $journal->branch->code ?? '---' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tax Type:</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $journal->tax_type)) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Narration -->
        <div class="mb-4">
            <h6 class="text-uppercase text-muted small fw-bold">Particulars / Description</h6>
            <p class="border p-2 bg-light rounded">{{ $journal->description }}</p>
        </div>

        <!-- Lines -->
        <table class="table table-custom table-striped mb-4">
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Details</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                    @if($journal->tax_type !== 'no_tax') <th class="text-end">Tax Amt</th> @endif
                </tr>
            </thead>
            <tbody>
                @foreach($journal->lines as $line)
                <tr>
                    <td>
                        <div class="fw-bold">{{ $line->account->code }}</div>
                        <div class="small text-muted">{{ $line->account->name }}</div>
                    </td>
                    <td>{{ $line->description }}</td>
                    <td class="text-end">{{ $line->debit > 0 ? number_format($line->debit, 2) : '-' }}</td>
                    <td class="text-end">{{ $line->credit > 0 ? number_format($line->credit, 2) : '-' }}</td>
                    @if($journal->tax_type !== 'no_tax')
                        <td class="text-end text-muted small">{{ number_format($line->tax_amount, 2) }}</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-top-2 border-dark">
                    <td colspan="2" class="text-end fw-bold">Subtotal</td>
                    @php
                        $totalTax = $journal->lines->sum('tax_amount');
                        $totalDebit = $journal->total_debit;
                        $totalCredit = $journal->total_credit;
                        $subtotalDebit = ($journal->tax_type == 'inclusive') ? $totalDebit - $totalTax : $totalDebit;
                        $subtotalCredit = ($journal->tax_type == 'inclusive') ? $totalCredit - $totalTax : $totalCredit;
                    @endphp
                    <td class="text-end fw-bold">{{ number_format($subtotalDebit, 2) }}</td>
                    <td class="text-end fw-bold">{{ number_format($subtotalCredit, 2) }}</td>
                    @if($journal->tax_type !== 'no_tax') <td></td> @endif
                </tr>
                @if($journal->tax_type !== 'no_tax')
                <tr>
                    <td colspan="2" class="text-end text-muted">Tax Total</td>
                    <td colspan="2" class="text-end text-muted">{{ number_format($totalTax, 2) }}</td>
                    <td></td>
                </tr>
                @endif
                <tr class="fs-5">
                    <td colspan="2" class="text-end fw-bold">Grand Total</td>
                    <td class="text-end fw-bold">{{ number_format($totalDebit, 2) }}</td>
                    <td class="text-end fw-bold">{{ number_format($totalCredit, 2) }}</td>
                    @if($journal->tax_type !== 'no_tax') <td></td> @endif
                </tr>
            </tfoot>
        </table>

        <!-- Approvals Section (Required for Audit) -->
        <div class="row mt-5 pt-4 no-print">
            <div class="col-4">
                <div class="border-top border-dark pt-2">
                    <small class="text-muted d-block mb-1">Prepared By:</small>
                    <div class="fw-bold">{{ $journal->creator->name ?? 'System' }}</div>
                    <small class="text-muted">{{ $journal->created_at->format('M d, Y') }}</small>
                </div>
            </div>
            <div class="col-4">
                <div class="border-top border-dark pt-2">
                    <small class="text-muted d-block mb-1">Reviewed By:</small>
                    <div class="fw-bold">____________________</div>
                </div>
            </div>
            <div class="col-4">
                <div class="border-top border-dark pt-2">
                    <small class="text-muted d-block mb-1">Approved By:</small>
                    <div class="fw-bold">____________________</div>
                </div>
            </div>
        </div>

        <!-- History & Audit Trail -->
        <div class="mt-5 no-print">
            <h6 class="fw-bold border-bottom pb-2">History & Activity Log</h6>
            <div class="list-group list-group-flush">
                @forelse($journal->activities ?? [] as $activity)
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <span class="badge bg-secondary me-2">{{ strtoupper($activity->action) }}</span>
                            <span class="fw-bold">{{ $activity->user->name ?? 'System' }}</span>
                            <span class="text-muted ms-1"><small>{{ $activity->description }}</small></span>
                        </div>
                        <small class="text-muted">{{ $activity->created_at->format('M d, Y h:i A') }}</small>
                    </div>
                @empty
                    <div class="text-muted fst-italic py-2">No activity recorded.</div>
                @endforelse
            </div>

            <div class="mt-5 pt-2 border-top text-center text-muted small text-uppercase">
                Generated on: {{ now()->format('M d, Y h:i A') }} | {{ config('app.name') }}
            </div>             
        </div>       
    </div>
</body>
</html>
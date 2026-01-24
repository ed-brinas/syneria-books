<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $invoice->type === 'invoice' ? 'Invoice' : 'Bill' }} #{{ $invoice->number ?? 'DRAFT' }}</title>
    
    <!-- Using Bootstrap CSS for layout consistency -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        body { background-color: #e9ecef; padding: 20px; color: #333; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        /* Paper Layout */
        .paper {
            background: white;
            max-width: 210mm; /* A4 Width */
            min-height: 297mm; /* A4 Height */
            margin: 0 auto;
            padding: 15mm;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
            border-radius: 2px;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        /* Status Stamp */
        .stamp {
            position: absolute; 
            top: 40px; 
            right: 40px;
            font-size: 2.5rem; 
            font-weight: 700; 
            text-transform: uppercase;
            opacity: 0.15;
            transform: rotate(-15deg); 
            border: 4px solid currentColor; 
            padding: 10px 20px;
            border-radius: 8px;
            user-select: none;
            z-index: 0;
        }
        .stamp.draft { color: #6c757d; }
        .stamp.review { color: #fd7e14; }
        .stamp.reviewed { color: #0dcaf0; }
        .stamp.posted { color: #0d6efd; }
        .stamp.paid { color: #198754; }
        .stamp.voided { color: #dc3545; }

        /* Typography */
        .doc-title { font-size: 2.5rem; font-weight: 800; color: #212529; letter-spacing: -1px; line-height: 1; }
        .company-name { font-size: 1.25rem; font-weight: 700; color: #495057; }
        .label-text { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; font-weight: 600; }
        
        /* Table Styling */
        .table-custom { width: 100%; margin-bottom: 2rem; }
        .table-custom thead th { 
            background-color: #f8f9fa; 
            color: #495057; 
            text-transform: uppercase; 
            font-size: 0.75rem; 
            letter-spacing: 0.5px; 
            padding: 12px 8px;
            border-bottom: 2px solid #dee2e6;
        }
        .table-custom tbody td { 
            padding: 12px 8px; 
            border-bottom: 1px solid #f1f3f5; 
            vertical-align: top;
            font-size: 0.9rem;
        }
        .table-custom tbody tr:last-child td { border-bottom: none; }

        /* Sections */
        .address-block { margin-bottom: 2rem; }
        .meta-table td { padding: 4px 0; vertical-align: top; }
        .meta-table .meta-label { color: #6c757d; padding-right: 15px; width: 100px; }
        .meta-table .meta-val { font-weight: 600; text-align: right; }

        .totals-table { width: 100%; min-width: 350px; margin-left: auto; border-collapse: collapse; }
        .totals-table td { padding: 6px 0; text-align: right; font-size: 0.9rem; }
        .totals-table .total-label { color: #6c757d; padding-right: 20px; width: 60%; }
        .totals-table .total-val { font-weight: 600; width: 40%; }
        .grand-total-row { font-size: 1.1rem; border-top: 2px solid #333; margin-top: 5px; padding-top: 5px; }
        
        /* Recurring Banner */
        .recurring-banner {
            background-color: #e8f4fd;
            border: 1px dashed #0d6efd;
            color: #0d6efd;
            padding: 10px;
            border-radius: 6px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Signatory Section */
        .signatory-section { margin-top: 40px; page-break-inside: avoid; }
        .signature-box { border-top: 1px solid #333; width: 200px; padding-top: 5px; margin-top: 40px; text-align: center; }
        .signature-name { font-weight: bold; text-transform: uppercase; font-size: 0.9rem; }
        .signature-label { font-size: 0.75rem; color: #666; text-transform: uppercase; }

        /* Attachments */
        .attachment-badge {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #0d6efd;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .attachment-badge:hover { background: #e9ecef; text-decoration: none; }

        @media print {
            body { background: white; padding: 0; }
            .paper { box-shadow: none; max-width: 100%; width: 100%; margin: 0; padding: 10mm; }
            .no-print { display: none !important; }
            .stamp { opacity: 0.1; border-width: 2px; }
        }
    </style>
</head>
<body>

    <!-- Toolbar -->
    <div class="container mb-4 no-print">
        <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border">
            <div>
                <a href="{{ route('invoices.index', ['type' => $invoice->type]) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>
            
            <div class="d-flex gap-2">
                {{-- Draft Actions --}}
                @if($invoice->status === 'draft' && $isBookkeeper)
                    <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i> Edit</a>
                    <form action="{{ route('invoices.submit', $invoice->id) }}" method="POST">@csrf
                        <button class="btn btn-primary btn-sm">Submit for Review</button>
                    </form>
                @endif

                {{-- Review Actions --}}
                @if($invoice->status === 'review' && $isReviewer)
                    <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn btn-warning btn-sm">Edit/Correct</a>
                    <form action="{{ route('invoices.reject', $invoice->id) }}" method="POST">@csrf
                        <button class="btn btn-danger btn-sm">Reject</button>
                    </form>
                    <form action="{{ route('invoices.approve', $invoice->id) }}" method="POST">@csrf
                        <button class="btn btn-success btn-sm">Approve</button>
                    </form>
                @endif

                {{-- Approval Actions --}}
                @if($invoice->status === 'reviewed' && $isApprover)
                    <form action="{{ route('invoices.reject', $invoice->id) }}" method="POST">@csrf
                        <button class="btn btn-danger btn-sm">Return to Draft</button>
                    </form>
                    <form action="{{ route('invoices.send', $invoice->id) }}" method="POST">@csrf
                        <button class="btn btn-primary btn-sm fw-bold"><i class="bi bi-send"></i> Post & Send</button>
                    </form>
                @endif

                {{-- Post-Actions --}}
                @if(in_array($invoice->status, ['posted', 'paid']) && $isApprover)
                    <form action="{{ route('invoices.void', $invoice->id) }}" method="POST" onsubmit="return confirm('Are you sure? This cannot be undone.');">@csrf
                        <button class="btn btn-outline-danger btn-sm">Void</button>
                    </form>
                @endif

                <button onclick="window.print()" class="btn btn-dark btn-sm"><i class="bi bi-printer"></i> Print / PDF</button>
            </div>
        </div>
    </div>

    <!-- The Document -->
    <div class="paper">
        
        <!-- Status Stamp -->
        <div class="stamp {{ $invoice->status }}">{{ strtoupper($invoice->status) }}</div>

        <!-- Recurring Information -->
        @if($invoice->is_recurring)
            <div class="recurring-banner no-print">
                <i class="bi bi-arrow-repeat fs-5"></i>
                <div>
                    <strong>Recurring Invoice:</strong> 
                    Repeats every {{ $invoice->recurrence_interval }} {{ $invoice->recurrence_type }}.
                    <span class="text-muted ms-1">Next due: {{ $invoice->next_recurrence_date ? $invoice->next_recurrence_date->format('M d, Y') : 'N/A' }}</span>
                </div>
            </div>
        @endif

        <!-- Header -->
        <div class="row mb-5 align-items-start">
            <div class="col-6">
                <!-- Logo Placeholer -->
                <div class="bg-light d-inline-flex align-items-center justify-content-center text-muted fw-bold rounded mb-3" style="width: 120px; height: 50px; border: 1px dashed #ccc;">
                    LOGO
                </div>
                <div class="company-name">{{ auth()->user()->tenant->company_name ?? 'Your Company Name' }}</div>
            </div>
            <div class="col-6 text-end">
                <h1 class="doc-title text-uppercase mb-4">{{ $invoice->type === 'invoice' ? 'INVOICE' : 'PURCHASE BILL' }}</h1>
                
                <div class="d-flex justify-content-end">
                    <table class="meta-table">
                        <tr>
                            <td class="meta-label">Number:</td>
                            <td class="meta-val text-primary">{{ $invoice->number ?? 'DRAFT' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Date:</td>
                            <td class="meta-val">{{ $invoice->date->format('M d, Y') }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Due Date:</td>
                            <td class="meta-val">{{ $invoice->due_date->format('M d, Y') }}</td>
                        </tr>
                        @if($invoice->reference)
                        <tr>
                            <td class="meta-label">Reference:</td>
                            <td class="meta-val">{{ $invoice->reference }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="meta-label">Currency:</td>
                            <td class="meta-val">{{ $invoice->currency_code }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Addresses -->
        <div class="row address-block">
            <div class="col-6">
                <div class="label-text mb-2">From</div>
                <div class="ps-3 border-start border-3 border-secondary">
                    <strong class="d-block">{{ auth()->user()->tenant->company_name ?? 'Your Company Name' }}</strong>
                    123 Business Road<br>
                    Commerce City, 12345<br>
                    Tax ID: 000-000-000
                </div>
            </div>
            <div class="col-6">
                <div class="label-text mb-2">{{ $invoice->type === 'invoice' ? 'Bill To' : 'Supplier' }}</div>
                <div class="ps-3 border-start border-3 border-primary">
                    <strong class="d-block text-primary">{{ $invoice->contact->name }}</strong>
                    @if($invoice->contact->company_name)
                        <div class="text-muted small">{{ $invoice->contact->company_name }}</div>
                    @endif
                    <div class="mt-1">
                        {!! nl2br(e($invoice->contact->address ?? 'No address on file')) !!}<br>
                        @if($invoice->contact->tax_number)
                            Tax ID: {{ $invoice->contact->tax_number }}
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes / Narration -->
        @if($invoice->notes)
        <div class="mb-4">
            <div class="label-text mb-1">Notes</div>
            <p class="bg-light p-3 rounded border" style="font-size: 0.9rem;">{{ $invoice->notes }}</p>
        </div>
        @endif

        <!-- Line Items -->
        <table class="table-custom table-striped">
            <thead>
                <tr>
                    <th style="width: 40%">Description</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Price</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>
                        <span class="fw-bold d-block">{{ $item->description }}</span>
                        <span class="small text-muted">{{ $item->account->code }} - {{ $item->account->name }}</span>
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-end fw-bold">{{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @php
            // Breakdown Logic
            $vatableSales = $invoice->items->filter(fn($i) => $i->tax_amount > 0)->sum('amount');
            
            // For Exempt/Zero-Rated, we check the item tax amount is 0, then look at Invoice Type
            // Note: If an invoice is mixed, this logic might need refinement per item, but based on current schema:
            $exemptSales = ($invoice->tax_type === 'vat_exempt') 
                ? $invoice->items->filter(fn($i) => $i->tax_amount == 0)->sum('amount') 
                : 0;
                
            $zeroRatedSales = ($invoice->tax_type === 'zero_rated')
                ? $invoice->items->filter(fn($i) => $i->tax_amount == 0)->sum('amount')
                : 0;
                
            // Total Sales (VAT Inclusive)
            // Logic: Vatable + Exempt + Zero + Tax
            // Or simpler: Grand Total before WHT
            $totalSalesVatInc = $invoice->grand_total; 

            // Calculations for display
            $lessVat = $invoice->tax_total;
            $netOfVat = $totalSalesVatInc - $lessVat;
            $lessWht = $invoice->withholding_tax_amount;
            $addVat = $invoice->tax_total; // Adding back per requirement
            $totalAmountDue = ($netOfVat - $lessWht) + $addVat;
        @endphp

        <!-- Totals & Payment Info -->
        <div class="row mt-4 mb-auto">
            <div class="col-6">
                <!--
                @if($invoice->type === 'invoice')
                <div class="p-3 bg-light rounded border">
                    <div class="label-text mb-2">Payment Details</div>
                    <p class="mb-0 small">
                        <strong>Bank:</strong> Global Bank Corp<br>
                        <strong>Account:</strong> 123-456789-00<br>
                        <strong>Terms:</strong> {{ $invoice->payment_terms ?? 'Due on Receipt' }}
                    </p>
                </div>
                @endif
                -->
                @if($invoice->attachments->count() > 0)
                <div class="mt-4 no-print">
                    <div class="label-text mb-2">Attachments</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($invoice->attachments as $file)
                            <a href="{{ Storage::disk('s3')->url($file->file_path) }}" target="_blank" class="attachment-badge">
                                <i class="bi bi-paperclip"></i> 
                                {{ Str::limit($file->file_name, 20) }}
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            
            <div class="col-6">
                <table class="totals-table">
                    <tr>
                        <td class="total-label">Vatable Sales</td>
                        <td class="total-val">{{ number_format($vatableSales, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="total-label">VAT-Exempt Sales</td>
                        <td class="total-val">{{ number_format($exemptSales, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="total-label">Zero-Rated Sales</td>
                        <td class="total-val">{{ number_format($zeroRatedSales, 2) }}</td>
                    </tr>
                    <tr style="border-top: 1px solid #ddd;">
                        <td class="total-label text-dark">Total Sales (VAT Inclusive)</td>
                        <td class="total-val">{{ number_format($totalSalesVatInc, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="total-label">Less: VAT</td>
                        <td class="total-val">({{ number_format($lessVat, 2) }})</td>
                    </tr>
                    <tr style="border-top: 1px solid #ddd;">
                        <td class="total-label text-dark">Amount: Net of VAT</td>
                        <td class="total-val">{{ number_format($netOfVat, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="total-label">Less: Withholding Tax</td>
                        <td class="total-val">({{ number_format($lessWht, 2) }})</td>
                    </tr>
                    <tr>
                        <td class="total-label">Add: VAT</td>
                        <td class="total-val">{{ number_format($addVat, 2) }}</td>
                    </tr>
                    <tr class="grand-total-row">
                        <td class="total-label text-dark">Total Amount Due</td>
                        <td class="total-val fs-5 text-dark">{{ $invoice->currency_code }} {{ number_format($totalAmountDue, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Signatory Section -->
        @php
            // Fetch Signatories using ActivityLog Workaround
            // Assumes App\Models\ActivityLog exists and tracks 'created' and 'reviewed' actions
            $preparedBy = \App\Models\ActivityLog::where('subject_type', get_class($invoice))
                            ->where('subject_id', $invoice->id)
                            ->where('action', 'created')
                            ->with('user')
                            ->first();
                            
            $checkedBy = \App\Models\ActivityLog::where('subject_type', get_class($invoice))
                            ->where('subject_id', $invoice->id)
                            ->whereIn('action', ['reviewed', 'approved']) // 'reviewed' or 'approved' depending on workflow
                            ->with('user')
                            ->latest()
                            ->first();
        @endphp

        <div class="row signatory-section">
            <div class="col-6">
                <div class="signature-box">
                    <div class="signature-name">{{ $preparedBy->user->name ?? 'System / Admin' }}</div>
                    <div class="signature-label">Prepared By</div>
                </div>
            </div>

            <div class="col-6 d-flex justify-content-end">
                <div class="signature-box">
                    <div class="signature-name">{{ $checkedBy->user->name ?? '' }}</div>
                    <div class="signature-label">{{ 'Approved By' ?? '' }}</div>
                </div>
            </div>

        </div>

        <!-- Footer / Generation Time -->
        <div class="mt-5 pt-2 border-top text-center text-muted small text-uppercase">
            Generated on: {{ now()->format('M d, Y h:i A') }}
        </div>

    </div>
</body>
</html>
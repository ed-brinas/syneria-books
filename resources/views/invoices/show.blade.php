<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $invoice->number ?? 'Invoice' }} - View</title>
    
    <!-- Fonts: Professional serif/sans-serif combination + Signature Font -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Serif:wght@400;700&family=Open+Sans:wght@400;600;700&family=Great+Vibes&display=swap');

        :root {
            --primary-color: #2c3e50;
            --secondary-color: #555;
            --border-color: #ddd;
        }

        body {
            background-color: #e9ecef;
            font-family: 'Open Sans', sans-serif;
            color: #333;
            margin: 0;
            padding: 30px;
        }

        /* The A4 Paper Sheet */
        .page {
            background: white;
            width: 210mm; /* A4 Width */
            min-height: 297mm; /* A4 Height */
            margin: 0 auto;
            padding: 20mm; /* Standard Print Margin */
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
            box-sizing: border-box;
        }

        /* Screen-only Toolbar */
        .toolbar {
            width: 210mm;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }
        
        .btn-secondary { background: #6c757d; }
        .btn:hover { opacity: 0.9; }

        /* --- INVOICE LAYOUT --- */

        /* Header Section */
        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .company-branding h1 {
            margin: 0;
            font-family: 'Noto Serif', serif;
            font-size: 26px;
            text-transform: uppercase;
            color: var(--primary-color);
            letter-spacing: 0.5px;
        }

        .company-details {
            margin-top: 5px;
            font-size: 12px;
            color: var(--secondary-color);
            line-height: 1.5;
        }

        .invoice-title-block {
            text-align: right;
        }

        .invoice-title {
            font-size: 24px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--primary-color);
            margin: 0 0 10px 0;
        }

        .meta-table {
            float: right;
            font-size: 13px;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 3px 0 3px 15px;
            text-align: right;
        }

        .meta-label {
            font-weight: 600;
            color: var(--secondary-color);
        }

        .meta-value {
            font-weight: 700;
            color: #000;
        }

        /* Customer / Bill To Section */
        .address-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 40px;
        }

        .bill-to-box {
            flex: 1;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid var(--primary-color);
        }

        .box-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #777;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .client-name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 5px;
            font-family: 'Noto Serif', serif;
        }

        .client-details {
            font-size: 12px;
            line-height: 1.6;
            color: #444;
        }

        /* Items Table */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-bottom: 30px;
        }

        .invoice-table th {
            background-color: var(--primary-color);
            color: white;
            text-align: left;
            padding: 10px 12px;
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 600;
        }

        .invoice-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .invoice-table tr:nth-child(even) { background-color: #fafafa; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .fw-bold { font-weight: 700; }

        /* Summary Section */
        .summary-container {
            display: flex;
            justify-content: flex-end;
            page-break-inside: avoid;
        }

        .summary-table {
            width: 50%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .summary-table td {
            padding: 6px 12px;
        }

        .summary-row td { border-bottom: 1px solid #eee; }
        .summary-label { text-align: right; color: #555; }
        .summary-value { text-align: right; font-weight: 600; font-family: monospace; font-size: 14px; }

        .grand-total-row {
            background-color: var(--primary-color);
            color: white;
        }
        
        .grand-total-row td {
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
            border: none;
        }
        
        .grand-total-row .summary-value { color: white; }

        /* Notes Section */
        .notes-section {
            margin-top: 20px;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            min-height: 50px;
        }

        /* Footer / Signatures */
        .footer-signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }

        .signature-block {
            width: 30%;
            text-align: center;
            position: relative; /* Needed for absolute positioning of signature */
        }

        .signature-line {
            border-top: 1px solid #aaa;
            margin-top: 40px;
            padding-top: 5px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
        }

        .signature-label {
            font-size: 10px;
            color: #777;
        }

        /* Digital Signature Styling */
        .digital-signature {
            font-family: 'Great Vibes', cursive;
            font-size: 28px;
            color: #000;
            position: absolute;
            bottom: 25px; /* Adjust to sit right on the line */
            left: 0;
            right: 0;
            margin: 0 auto;
            pointer-events: none;
        }

        .digital-signature-img {
            max-height: 60px;
            max-width: 100%;
            position: absolute;
            bottom: 15px;
            left: 0;
            right: 0;
            margin: 0 auto;
        }

        /* Status Stamp */
        .stamp {
            position: absolute;
            top: 180px;
            right: 40px;
            font-size: 50px;
            font-weight: 900;
            text-transform: uppercase;
            color: rgba(220, 53, 69, 0.15); /* Default redish */
            border: 4px solid rgba(220, 53, 69, 0.15);
            padding: 10px 20px;
            transform: rotate(-20deg);
            z-index: 0;
            pointer-events: none;
            user-select: none;
        }
        .stamp.paid { color: rgba(25, 135, 84, 0.2); border-color: rgba(25, 135, 84, 0.2); } /* Green */
        .stamp.posted { color: rgba(13, 110, 253, 0.15); border-color: rgba(13, 110, 253, 0.15); } /* Blue */

        /* Print Specifics */
        @media print {
            body { 
                background: white; 
                margin: 0; 
                padding: 0; 
            }
            .page {
                width: 100%;
                margin: 0;
                padding: 15mm;
                box-shadow: none;
                border: none;
            }
            .toolbar { display: none !important; }
            .btn { display: none !important; }
            
            /* Enforce background colors for table headers etc */
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body>

    <!-- Actions Toolbar -->
    <div class="toolbar">
        <a href="{{ route('invoices.index', ['type' => $invoice->type]) }}" class="btn btn-secondary">
            &larr; Back
        </a>
        <div style="display: flex; gap: 10px;">
            @if($invoice->status === 'draft')
                <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn" style="background-color: #ffc107; color: #000;">
                    Edit Draft
                </a>
            @endif
            <button onclick="window.print()" class="btn">
                Print / Save PDF
            </button>
        </div>
    </div>

    <!-- Document Sheet -->
    <div class="page">

        <!-- Status Watermark -->
        @if($invoice->status === 'draft')
            <div class="stamp">DRAFT</div>
        @elseif($invoice->status === 'voided')
            <div class="stamp" style="color: #dc3545; border-color: #dc3545;">VOIDED</div>
        @elseif($invoice->status === 'posted')
            <div class="stamp posted">POSTED</div>
        @elseif($invoice->status === 'paid')
            <div class="stamp paid">PAID</div>
        @endif

        <!-- Header -->
        <div class="header">
            <div class="company-branding">
                {{-- Tenant/Company Information --}}
                <h1>{{ auth()->user()->tenant->company_name ?? 'Your Company Name' }}</h1>
                <div class="company-details">
                    {{ auth()->user()->tenant->business_address ?? '123 Business Street' }}<br>
                    {{ auth()->user()->tenant->city ?? 'City' }} 
                    {{ auth()->user()->tenant->postal_code ?? '' }}, 
                    {{ auth()->user()->tenant->country ?? 'Country' }}<br>
                    
                    @if(auth()->user()->tenant->tax_identification_number)
                        <strong>VAT REG TIN:</strong> {{ auth()->user()->tenant->tax_identification_number }}
                    @endif
                    <br>
                    @if(auth()->user()->tenant->phone)
                        Phone: {{ auth()->user()->tenant->phone }}
                    @endif
                </div>
            </div>

            <div class="invoice-title-block">
                {{-- Dynamic Title based on Subtype --}}
                <div class="invoice-title">
                    @if($invoice->type === 'bill')
                        PURCHASE BILL
                    @elseif($invoice->subtype === 'sales_invoice')
                        SALES INVOICE
                    @elseif($invoice->subtype === 'service_invoice')
                        BILLING STATEMENT
                    @else
                        INVOICE
                    @endif
                </div>

                <table class="meta-table">
                    <tr>
                        <td class="meta-label">Number:</td>
                        <td class="meta-value" style="font-family: monospace; font-size: 16px;">
                            {{ $invoice->number ?? '---' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-label">Date:</td>
                        <td class="meta-value">{{ $invoice->date ? $invoice->date->format('d-M-Y') : '--' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Due Date:</td>
                        <td class="meta-value">{{ $invoice->due_date ? $invoice->due_date->format('d-M-Y') : '--' }}</td>
                    </tr>
                    @if($invoice->reference)
                    <tr>
                        <td class="meta-label">Reference:</td>
                        <td class="meta-value">{{ $invoice->reference }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Addresses -->
        <div class="address-container">
            <!-- SOLD TO / BILL TO -->
            <div class="bill-to-box">
                <div class="box-label">
                    {{ $invoice->type === 'bill' ? 'From (Supplier)' : 'Sold To (Customer)' }}
                </div>
                <div class="client-name">{{ $invoice->contact->name ?? 'Cash Customer' }}</div>
                <div class="client-details">
                    {{ $invoice->contact->address ?? '' }}<br>
                    {{ $invoice->contact->city ?? '' }} {{ $invoice->contact->zip_code ?? '' }}<br>
                    
                    @if(!empty($invoice->contact->tax_number))
                        <br><strong>TIN:</strong> {{ $invoice->contact->tax_number }}
                    @endif
                    @if(!empty($invoice->contact->business_style))
                        <br><strong>Bus. Style:</strong> {{ $invoice->contact->business_style }}
                    @endif
                </div>
            </div>

            <!-- Optional: SHIP TO (Use same as bill to if not present) -->
            @if($invoice->type === 'invoice')
            <div style="flex: 1; padding: 15px;">
                <div class="box-label">Ship To / Instructions</div>
                <div class="client-details">
                    <!-- If you have separate shipping address logic, put it here. Otherwise: -->
                    Same as Sold To Address<br><br>
                    <span style="color: #777;">Terms:</span> {{ ucfirst(str_replace('_', ' ', $invoice->payment_terms ?? 'COD')) }}
                </div>
            </div>
            @endif
        </div>

        <!-- Items Table -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th class="text-center" style="width: 15%;">Qty</th>
                    <th class="text-right" style="width: 15%;">Unit Price</th>
                    <th class="text-right" style="width: 20%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>
                        <span style="font-weight: 600;">{{ $item->description }}</span>
                        <!-- Optional: Account code if needed for internal bills -->
                        <!-- <div style="font-size: 10px; color: #888;">Code: {{ $item->account->code ?? '' }}</div> -->
                    </td>
                    <td class="text-center">{{ (float)$item->quantity == (int)$item->quantity ? (int)$item->quantity : $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right fw-bold">{{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach

                <!-- Empty rows filler (optional for aesthetics) -->
                @if($invoice->items->count() < 3)
                    @for($i = 0; $i < (3 - $invoice->items->count()); $i++)
                        <tr><td style="height: 35px;"></td><td></td><td></td><td></td></tr>
                    @endfor
                @endif
            </tbody>
        </table>

        <!-- Totals & Notes -->
        <div class="summary-container">
            <table class="summary-table">
                <!-- Subtotal -->
                <tr class="summary-row">
                    <td class="summary-label">Subtotal</td>
                    <td class="summary-value">{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>

                <!-- Tax Logic -->
                <!-- Assuming standard VAT (12%) or similar logic. 
                     Since controller stores tax_total, we show it here. -->
                @if($invoice->tax_total > 0)
                <tr class="summary-row">
                    <td class="summary-label">VAT / Tax</td>
                    <td class="summary-value">{{ number_format($invoice->tax_total, 2) }}</td>
                </tr>
                @endif

                @if($invoice->tax_type === 'vat_exempt')
                <tr class="summary-row">
                    <td class="summary-label">VAT Exempt Sales</td>
                    <td class="summary-value">{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @endif

                <!-- Grand Total -->
                <tr class="grand-total-row">
                    <td class="summary-label" style="color: white;">TOTAL AMOUNT</td>
                    <td class="summary-value">{{ number_format($invoice->grand_total, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="notes-section">
            <strong>Notes / Terms:</strong><br>
            {{ $invoice->notes ?? 'Thank you for your business.' }}
            
            @if($invoice->status === 'draft')
                <br><em style="color: #dc3545;">(This is a draft document and not valid for tax purposes)</em>
            @endif
        </div>

        <!-- Footer Signatures (Philippines / Formal Format) -->
        <div class="footer-signatures">
            <div class="signature-block">
                <!-- Signature Logic: Image or Font -->
                @if(isset(auth()->user()->signature_path) && auth()->user()->signature_path)
                    <!-- Case 1: Real Image stored in User model -->
                    <img src="{{ asset(auth()->user()->signature_path) }}" class="digital-signature-img" alt="Signature">
                @else
                    <!-- Case 2: Cursive Font Fallback -->
                    <div class="digital-signature">
                        {{ auth()->user()->name ?? 'Authorized Rep' }}
                    </div>
                @endif
                
                <div class="signature-line">Prepared By</div>
                <div class="signature-label">{{ auth()->user()->name ?? 'Authorized Rep' }}</div>
            </div>

            <div class="signature-block">
                <div class="signature-line">Approved By</div>
                <div class="signature-label">Finance Manager / Authorized Signatory</div>
            </div>

            <!-- "Received By" is very common in PH/International Delivery -->
            <div class="signature-block">
                <div class="signature-line">Received By</div>
                <div class="signature-label">Customer Signature & Date</div>
            </div>
        </div>

        <!-- System Footnote -->
        <div style="margin-top: 40px; font-size: 9px; color: #aaa; text-align: center;">
            System Generated Invoice | {{ config('app.name') }} | {{ date('d-m-Y H:i') }}
        </div>

    </div>
</body>
</html>
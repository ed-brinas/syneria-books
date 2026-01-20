<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal Voucher {{ $journal->reference }}</title>
    
    <!-- Fonts: Standard serif for documents -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Serif:wght@400;700&family=Open+Sans:wght@400;600&display=swap');

        :root {
            --primary-color: #333;
            --border-color: #ddd;
        }

        body {
            background-color: #f5f5f5;
            font-family: 'Open Sans', sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        /* The Paper Sheet */
        .page {
            background: white;
            width: 210mm; /* A4 Width */
            min-height: 297mm; /* A4 Height */
            margin: 0 auto;
            padding: 20mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            box-sizing: border-box;
        }

        /* Toolbar (Hide on Print) */
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
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-secondary { background: #6c757d; }
        .btn:hover { opacity: 0.9; }

        /* Header Layout */
        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }

        .company-info h1 {
            margin: 0;
            font-family: 'Noto Serif', serif;
            font-size: 24px;
            text-transform: uppercase;
        }

        .company-info p {
            margin: 5px 0 0;
            font-size: 12px;
            color: #555;
            line-height: 1.4;
        }

        .document-title {
            text-align: right;
        }

        .document-title h2 {
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
            color: #000;
        }

        .meta-table {
            margin-top: 10px;
            font-size: 12px;
            text-align: right;
        }

        .meta-table td { padding: 2px 0 2px 10px; }
        .meta-label { font-weight: bold; color: #555; }

        /* Description Block */
        .narration-block {
            background: #f9f9f9;
            padding: 10px;
            border: 1px solid #eee;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .narration-label {
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            color: #777;
            display: block;
            margin-bottom: 4px;
        }

        /* Journal Table */
        .journal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 40px;
        }

        .journal-table th {
            background: #f0f0f0;
            border-bottom: 1px solid #333;
            border-top: 1px solid #333;
            text-align: left;
            padding: 8px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .journal-table td {
            border-bottom: 1px solid #eee;
            padding: 8px;
            vertical-align: top;
        }

        .journal-table .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }

        .journal-table tr:last-child td {
            border-bottom: 2px solid #333;
        }

        /* Totals Row */
        .totals-row td {
            font-weight: bold;
            border-top: 2px solid #333 !important;
            border-bottom: none !important;
            background: #fafafa;
            padding: 10px 8px;
        }

        /* Signatures Footer */
        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 30%;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .signature-title {
            font-size: 10px;
            color: #777;
        }

        /* Status Stamp */
        .stamp {
            position: absolute;
            top: 200px;
            right: 50px;
            font-size: 40px;
            font-weight: bold;
            text-transform: uppercase;
            color: rgba(255, 0, 0, 0.2);
            border: 4px solid rgba(255, 0, 0, 0.2);
            padding: 10px 20px;
            transform: rotate(-15deg);
            z-index: 0;
            pointer-events: none;
        }

        .stamp.posted { color: rgba(0, 128, 0, 0.2); border-color: rgba(0, 128, 0, 0.2); }
        .stamp.draft { color: rgba(128, 128, 128, 0.2); border-color: rgba(128, 128, 128, 0.2); }

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
                padding: 10mm; /* Slightly smaller padding for print margins */
                box-shadow: none;
            }
            .toolbar { display: none; }
            
            /* Ensure background graphics (like the stamp) print */
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body>

    <!-- Actions Bar (Hidden on Print) -->
    <div class="toolbar">
        <a href="{{ route('journals.index') }}" class="btn btn-secondary">&larr; Back to List</a>
        <button onclick="window.print()" class="btn">
            Print / Save as PDF
        </button>
    </div>

    <!-- The Document Sheet -->
    <div class="page">
        
        <!-- Watermark Stamp -->
        @if($journal->status === 'posted')
            <div class="stamp posted">POSTED</div>
        @elseif($journal->status === 'draft')
            <div class="stamp draft">DRAFT</div>
        @elseif($journal->status === 'voided')
            <div class="stamp">VOIDED</div>
        @endif

        <!-- Header -->
        <div class="header">
            <div class="company-info">
                {{-- Tenant Information --}}
                <h1>{{ auth()->user()->tenant->company_name ?? 'Company Name' }}</h1>
                <p>
                    {{ auth()->user()->tenant->business_address ?? 'Business Address' }}<br>
                    {{ auth()->user()->tenant->city ?? 'City' }} 
                    @if(auth()->user()->tenant->postal_code) 
                        - {{ auth()->user()->tenant->postal_code }} 
                    @endif
                    <br>
                    {{ auth()->user()->tenant->country ?? 'Country' }}
                </p>
                
                {{-- Tax and Reg IDs line --}}
                <p style="margin-top: 5px;">
                    @if(auth()->user()->tenant->tax_identification_number)
                        <strong>Tax ID:</strong> {{ auth()->user()->tenant->tax_identification_number }}
                    @endif
                    
                    @if(auth()->user()->tenant->tax_identification_number && auth()->user()->tenant->company_reg_number)
                        <span style="margin: 0 5px;">|</span>
                    @endif

                    @if(auth()->user()->tenant->company_reg_number)
                        <strong>Reg No:</strong> {{ auth()->user()->tenant->company_reg_number }}
                    @endif
                </p>
            </div>
            
            <div class="document-title">
                <h2>Journal Voucher</h2>
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">Reference No:</td>
                        <td>{{ $journal->reference ?? '---' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Date:</td>
                        <td>{{ $journal->date->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Status:</td>
                        <td style="text-transform: uppercase;">{{ $journal->status }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Description / Narration -->
        <div class="narration-block">
            <span class="narration-label">Description / Narration</span>
            {{ $journal->description }}
        </div>

        <!-- Lines Table -->
        <table class="journal-table">
            <thead>
                <tr>
                    <th style="width: 15%">Account Code</th>
                    <th style="width: 30%">Account Name</th>
                    <th style="width: 35%">Line Description</th>
                    <th style="width: 10%" class="amount">Debit</th>
                    <th style="width: 10%" class="amount">Credit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($journal->lines as $line)
                <tr>
                    <td>{{ $line->account->code }}</td>
                    <td>{{ $line->account->name }}</td>
                    <td style="color: #555;">{{ $line->description }}</td>
                    <td class="amount">
                        @if($line->debit > 0)
                            {{ number_format($line->debit, 2) }}
                        @endif
                    </td>
                    <td class="amount">
                        @if($line->credit > 0)
                            {{ number_format($line->credit, 2) }}
                        @endif
                    </td>
                </tr>
                @endforeach

                <!-- Fill empty rows for visual height if needed (optional) -->
                @if($journal->lines->count() < 5)
                    @for($i = 0; $i < (5 - $journal->lines->count()); $i++)
                    <tr>
                        <td>&nbsp;</td><td></td><td></td><td></td><td></td>
                    </tr>
                    @endfor
                @endif
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td colspan="3" style="text-align: right; text-transform: uppercase;">Total</td>
                    <td class="amount">{{ number_format($journal->total_debit, 2) }}</td>
                    <td class="amount">{{ number_format($journal->total_credit, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures -->
        <div class="footer">
            <div class="signature-box">
                <div style="height: 30px;">
                    <!-- Optional: Insert Digital Signature Img here -->
                    @if($journal->creator)
                        <span style="font-family: 'Brush Script MT', cursive; font-size: 24px;">{{ $journal->creator->name }}</span>
                    @endif
                </div>
                <div class="signature-line">
                    {{ $journal->creator->name ?? 'Unknown User' }}
                </div>
                <div class="signature-title">Prepared By</div>
            </div>

            <div class="signature-box">
                <div style="height: 30px;"></div>
                <div class="signature-line"></div>
                <div class="signature-title">Reviewed By</div>
            </div>

            <div class="signature-box">
                <div style="height: 30px;"></div>
                <div class="signature-line"></div>
                <div class="signature-title">Approved By</div>
            </div>
        </div>
        
        <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #999;">
            Printed on {{ date('d M Y H:i A') }} | System Generated Document
        </div>

    </div>

</body>
</html>
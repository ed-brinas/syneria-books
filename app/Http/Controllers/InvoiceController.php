<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceAttachment;
use App\Models\Contact;
use App\Models\Account;
use App\Models\TaxRate;
use App\Models\ActivityLog;
use App\Models\Sequence;
use App\Models\Branch; // Added for Multi-Branch support
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Symfony\Component\Intl\Currencies; 

class InvoiceController extends Controller
{
    // --- Configuration ---
    
    private function getCurrencies()
    {
        return Currencies::getNames();
    }

    // --- Helpers ---
    private function userHasRole(array $allowedRoles)
    {
        $userRole = strtolower(Auth::user()->role ?? '');
        return in_array($userRole, array_map('strtolower', $allowedRoles));
    }

    private function redirectUnauthorized($message = 'Unauthorized action.')
    {
        return back()->with('error', $message);
    }

    // --- Actions ---

    public function index(Request $request)
    {
        $type = $request->query('type', 'invoice');
        
        $query = Invoice::with(['contact', 'branch']) // Eager load branch
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('type', $type);

        // Filter by Status
        if ($request->has('status') && $request->status !== 'all' && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Filter by Branch (New)
        if ($request->has('branch_id') && !empty($request->branch_id)) {
            $query->where('branch_id', $request->branch_id);
        }

        // Search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhereHas('contact', function($c) use ($search) {
                      $c->where('company_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->orderBy('date', 'desc')->paginate(15)->withQueryString();

        return view('invoices.index', compact('invoices', 'type'));
    }

    public function create()
    {
        $contacts = Contact::where('type', 'customer')->orderBy('company_name')->get();
        $accounts = Account::where('type', 'Revenue')->where('is_active', true)->get(); 
        $taxRates = TaxRate::where('is_active', true)->get();
        
        // Load Branches for dropdown (New)
        $branches = Branch::orderBy('is_default', 'desc')->orderBy('name')->get();
        
        return view('invoices.create', compact('contacts', 'accounts', 'taxRates', 'branches'));
    }

    public function store(Request $request)
    {
        $this->validateInvoice($request);

        $tenant = auth()->user()->tenant;
        
        // --- Branch Resolution Logic ---
        // 1. Try to use the submitted branch_id
        // 2. If null, fallback to the Tenant's "Main/Default" branch
        $defaultBranch = $tenant->mainBranch;
        $branchId = $request->branch_id ?? optional($defaultBranch)->id;

        // Security Check: Ensure the resolved branch actually belongs to this tenant
        if ($branchId) {
             $branch = Branch::where('id', $branchId)->where('tenant_id', $tenant->id)->firstOrFail();
        }

        DB::transaction(function () use ($request, $branchId) {
            $tenantId = auth()->user()->tenant_id;

            // Generate Number 
            // Note: Ideally, you pass $branchId to Sequence::generate() if you want per-branch numbering
            $number = 'INV-' . strtoupper(uniqid()); 

            $invoice = Invoice::create([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId, // Set Branch
                'contact_id' => $request->contact_id,
                'type' => 'invoice',
                'subtype' => 'standard',
                'number' => $number, 
                'reference' => $request->reference,
                'date' => $request->date,
                'due_date' => $request->due_date,
                'status' => $request->status ?? 'draft',
                'currency_code' => $request->currency_code ?? 'PHP',
                'notes' => $request->notes,
                
                // Recurrence
                'is_recurring' => $request->has('is_recurring'),
                'recurrence_interval' => $request->recurrence_interval,
                'recurrence_type' => $request->recurrence_type,
                'recurrence_end_date' => $request->recurrence_end_date,

                // Amounts (Will be recalculated)
                'subtotal' => 0,
                'tax_total' => 0,
                'withholding_tax_rate' => $request->withholding_tax_rate ?? 0,
                'withholding_tax_amount' => 0,
                'grand_total' => 0,
            ]);

            // Save Items
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($request->items as $itemData) {
                $lineTotal = $itemData['quantity'] * $itemData['unit_price'];
                $lineTax = 0;

                // Handle Tax
                if (!empty($itemData['tax_rate_id'])) {
                    $taxRate = TaxRate::find($itemData['tax_rate_id']);
                    if ($taxRate) {
                        $lineTax = $lineTotal * ($taxRate->rate / 100);
                    }
                }

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'account_id' => $itemData['account_id'],
                    'tax_rate_id' => $itemData['tax_rate_id'] ?? null,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'amount' => $lineTotal,
                    'tax_amount' => $lineTax
                ]);

                $subtotal += $lineTotal;
                $taxTotal += $lineTax;
            }

            // Calculate Withholding Tax
            $whtAmount = 0;
            if ($invoice->withholding_tax_rate > 0) {
                // Calculation: WHT is deducted from Grand Total (Receivable), 
                // but usually calculated on the Net of VAT or Gross depending on local regulations.
                // Here assuming applied on Subtotal (Net of VAT).
                $whtAmount = $subtotal * ($invoice->withholding_tax_rate / 100);
            }

            $grandTotal = ($subtotal + $taxTotal) - $whtAmount;

            $invoice->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'withholding_tax_amount' => $whtAmount,
                'grand_total' => $grandTotal
            ]);

            // Upload Attachments
            if ($request->hasFile('attachments')) {
                $this->uploadAttachments($request->file('attachments'), $invoice);
            }

            $this->logActivity($invoice, 'created', "Created Invoice {$invoice->number}");
        });

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        if ($invoice->tenant_id !== auth()->user()->tenant_id) abort(403);
        
        $invoice->load(['items.account', 'items.taxRate', 'contact', 'attachments', 'branch']);
        return view('invoices.show', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->tenant_id !== auth()->user()->tenant_id) abort(403);

        $this->validateInvoice($request);
        // Note: We generally do NOT allow updating the Branch ID on an existing invoice 
        // to prevent accounting inconsistencies, so branch_id is excluded here.

        DB::transaction(function () use ($request, $invoice) {
            
            $invoice->update([
                'contact_id' => $request->contact_id,
                'reference' => $request->reference,
                'date' => $request->date,
                'due_date' => $request->due_date,
                'status' => $request->status ?? $invoice->status,
                'currency_code' => $request->currency_code ?? 'PHP',
                'notes' => $request->notes,
                'is_recurring' => $request->has('is_recurring'),
                'recurrence_interval' => $request->recurrence_interval,
                'recurrence_type' => $request->recurrence_type,
                'recurrence_end_date' => $request->recurrence_end_date,
                'withholding_tax_rate' => $request->withholding_tax_rate ?? 0,
            ]);

            // Clear old items and re-create (Simplest approach for MVP)
            $invoice->items()->delete();

            $subtotal = 0;
            $taxTotal = 0;

            foreach ($request->items as $itemData) {
                $lineTotal = $itemData['quantity'] * $itemData['unit_price'];
                $lineTax = 0;

                if (!empty($itemData['tax_rate_id'])) {
                    $taxRate = TaxRate::find($itemData['tax_rate_id']);
                    if ($taxRate) {
                        $lineTax = $lineTotal * ($taxRate->rate / 100);
                    }
                }

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'account_id' => $itemData['account_id'],
                    'tax_rate_id' => $itemData['tax_rate_id'] ?? null,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'amount' => $lineTotal,
                    'tax_amount' => $lineTax
                ]);

                $subtotal += $lineTotal;
                $taxTotal += $lineTax;
            }

            $whtAmount = 0;
            if ($invoice->withholding_tax_rate > 0) {
                $whtAmount = $subtotal * ($invoice->withholding_tax_rate / 100);
            }

            $grandTotal = ($subtotal + $taxTotal) - $whtAmount;

            $invoice->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'withholding_tax_amount' => $whtAmount,
                'grand_total' => $grandTotal
            ]);

            if ($request->hasFile('attachments')) {
                $this->uploadAttachments($request->file('attachments'), $invoice);
            }

            $this->logActivity($invoice, 'updated', "Updated Invoice {$invoice->number}");
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->tenant_id !== auth()->user()->tenant_id) abort(403);
        
        $invoice->delete();
        $this->logActivity($invoice, 'deleted', "Deleted Invoice {$invoice->number}");
        
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }

    // --- Private Methods ---

    private function validateInvoice($request)
    {
        $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:date',
            
            // Branch is optional in validation because we fallback to default if missing
            'branch_id' => 'nullable|exists:branches,id', 

            // Items
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.account_id' => 'required|exists:accounts,id',
            
            // Attachments
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|max:10240',
            
            // Withholding Tax
            'withholding_tax_rate' => 'nullable|numeric|min:0|max:100',

            // Recurring Validation
            'recurrence_interval' => 'nullable|required_if:is_recurring,on|integer|min:1',
            'recurrence_type' => 'nullable|required_if:is_recurring,on|in:weeks,months',
            'recurrence_end_date' => 'nullable|date|after:date',
        ]);
    }

    private function uploadAttachments($files, $invoice)
    {
        foreach ($files as $file) {
            $path = $file->storePublicly('accounting-invoices/' . auth()->user()->tenant_id, 's3');
            
            InvoiceAttachment::create([
                'invoice_id' => $invoice->id,
                'tenant_id' => auth()->user()->tenant_id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }
    }

    private function logActivity($invoice, $action, $desc)
    {
        ActivityLog::create([
            'tenant_id' => $invoice->tenant_id,
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $desc,
            'subject_type' => Invoice::class,
            'subject_id' => $invoice->id,
            'ip_address' => request()->ip()
        ]);
    }
}
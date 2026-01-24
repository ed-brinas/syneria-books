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
        
        $query = Invoice::with(['contact'])
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('type', $type);

        if ($request->has('status') && $request->status !== 'all' && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhereHas('contact', function($c) use ($search) {
                      $c->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->orderBy('date', 'desc')->paginate(15);
        $invoices->appends($request->all());
        
        $isBookkeeper = $this->userHasRole(['bookkeeper', 'admin']);
        $isReviewer = $this->userHasRole(['reviewer', 'approver', 'admin']);
        $isApprover = $this->userHasRole(['approver', 'admin']);

        return view('invoices.index', compact('invoices', 'type', 'isBookkeeper', 'isReviewer', 'isApprover'));
    }

    public function create(Request $request)
    {
        if (!$this->userHasRole(['bookkeeper', 'admin'])) {
            return $this->redirectUnauthorized('Only Bookkeepers can create invoices.');
        }

        $type = $request->query('type', 'invoice');
        $tenantId = auth()->user()->tenant_id;
        
        $contacts = Contact::where('tenant_id', $tenantId)->orderBy('name')->get();
        
        $accountType = ($type === 'invoice') ? 'Revenue' : 'Expense';
        $accounts = Account::where('tenant_id', $tenantId)->where('type', $accountType)->orderBy('code')->get();

        $taxRates = TaxRate::where('tenant_id', $tenantId)->active()->orderBy('name')->get();
        
        $currencies = $this->getCurrencies();

        return view('invoices.create', compact('type', 'contacts', 'accounts', 'taxRates', 'currencies'));
    }

    public function store(Request $request)
    {
        if (!$this->userHasRole(['bookkeeper', 'admin'])) return $this->redirectUnauthorized();

        $this->validateInvoice($request);

        try {
            DB::beginTransaction();
            
            // 1. Calculate Totals
            $calculations = $this->calculateInvoiceTotals($request->items);
            
            // 2. Calculate Withholding Tax
            $whtRate = (float) $request->withholding_tax_rate;
            $whtAmount = $calculations['subtotal'] * ($whtRate / 100);

            // 3. Recurrence Logic
            $nextRecurrence = null;
            if ($request->has('is_recurring')) {
                $interval = (int) $request->recurrence_interval;
                $unit = $request->recurrence_type;
                if ($interval > 0 && in_array($unit, ['weeks', 'months'])) {
                    $nextRecurrence = Carbon::parse($request->date)->add($unit, $interval);
                }
            }

            $invoice = Invoice::create([
                'tenant_id' => auth()->user()->tenant_id,
                'contact_id' => $request->contact_id,
                'type' => $request->type,
                'subtype' => $request->subtype ?? 'standard',
                'tax_type' => $request->tax_type,
                'payment_terms' => $request->payment_terms,
                'currency_code' => $request->currency_code ?? 'USD',
                'number' => null, // Generated on Post
                'reference' => $request->reference,
                'date' => $request->date,
                'due_date' => $request->due_date,
                'status' => 'draft',
                
                // Financials
                'subtotal' => $calculations['subtotal'],
                'tax_total' => $calculations['tax_total'],
                'grand_total' => $calculations['grand_total'], // Face Value
                'withholding_tax_rate' => $whtRate,
                'withholding_tax_amount' => $whtAmount,
                'notes' => $request->notes,

                // Recurrence
                'is_recurring' => $request->has('is_recurring'),
                'recurrence_interval' => $request->has('is_recurring') ? $request->recurrence_interval : null,
                'recurrence_type' => $request->has('is_recurring') ? $request->recurrence_type : null,
                'recurrence_end_date' => $request->has('is_recurring') ? $request->recurrence_end_date : null,
                'next_recurrence_date' => $nextRecurrence,
            ]);

            foreach ($calculations['items'] as $row) {
                InvoiceItem::create(array_merge(['invoice_id' => $invoice->id], $row));
            }

            if ($request->hasFile('attachments')) {
                $this->uploadAttachments($request->file('attachments'), $invoice);
            }

            $this->logActivity($invoice, 'created', "Created Draft Invoice");
            
            if ($request->has('send_email_copy') && $invoice->contact->email) {
                $this->logActivity($invoice, 'email_queued', "Queued email copy for contact: {$invoice->contact->email}");
            }

            DB::commit();

            if ($request->action === 'submit') {
                return $this->submit($invoice);
            }

            return redirect()->route('invoices.index', ['type' => $request->type])->with('success', 'Draft saved.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function edit(Invoice $invoice)
    {
        if ($invoice->tenant_id !== auth()->user()->tenant_id) abort(403);

        $canEdit = ($invoice->status === 'draft' && $this->userHasRole(['bookkeeper', 'admin'])) || 
                   ($invoice->status === 'review' && $this->userHasRole(['bookkeeper', 'reviewer', 'admin']));

        if (!$canEdit) return redirect()->route('invoices.index', ['type' => $invoice->type])->with('error', 'Cannot edit.');

        $type = $invoice->type;
        $tenantId = auth()->user()->tenant_id;
        $contactType = ($type === 'invoice') ? 'customer' : 'supplier';
        
        $contacts = Contact::where('tenant_id', $tenantId)->where('type', $contactType)->orderBy('name')->get();
        $accountType = ($type === 'invoice') ? 'Revenue' : 'Expense';
        $accounts = Account::where('tenant_id', $tenantId)->where('type', $accountType)->orderBy('code')->get();
        
        $taxRates = TaxRate::where('tenant_id', $tenantId)->active()->orderBy('name')->get();
        $currencies = $this->getCurrencies();

        $invoice->load('items', 'attachments');

        return view('invoices.create', compact('invoice', 'type', 'contacts', 'accounts', 'taxRates', 'currencies'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->tenant_id !== auth()->user()->tenant_id) abort(403);
        if ($invoice->status === 'posted' || $invoice->status === 'paid') return back()->with('error', 'Cannot update posted invoices.');

        $this->validateInvoice($request);

        try {
            DB::beginTransaction();

            $calculations = $this->calculateInvoiceTotals($request->items);
            
            // Calculate Withholding Tax
            $whtRate = (float) $request->withholding_tax_rate;
            $whtAmount = $calculations['subtotal'] * ($whtRate / 100);

            // Recurrence Logic
            $nextRecurrence = $invoice->next_recurrence_date;
            if ($request->has('is_recurring')) {
                if (!$nextRecurrence || $request->date != $invoice->date) {
                    $interval = (int) $request->recurrence_interval;
                    $unit = $request->recurrence_type;
                    if ($interval > 0) {
                        $nextRecurrence = Carbon::parse($request->date)->add($unit, $interval);
                    }
                }
            } else {
                $nextRecurrence = null;
            }

            $invoice->update([
                'contact_id' => $request->contact_id,
                'subtype' => $request->subtype ?? 'standard',
                'tax_type' => $request->tax_type,
                'payment_terms' => $request->payment_terms,
                'currency_code' => $request->currency_code ?? 'USD',
                'reference' => $request->reference,
                'date' => $request->date,
                'due_date' => $request->due_date,
                
                // Financials
                'subtotal' => $calculations['subtotal'],
                'tax_total' => $calculations['tax_total'],
                'grand_total' => $calculations['grand_total'],
                'withholding_tax_rate' => $whtRate,
                'withholding_tax_amount' => $whtAmount,
                'notes' => $request->notes,

                // Recurrence
                'is_recurring' => $request->has('is_recurring'),
                'recurrence_interval' => $request->has('is_recurring') ? $request->recurrence_interval : null,
                'recurrence_type' => $request->has('is_recurring') ? $request->recurrence_type : null,
                'recurrence_end_date' => $request->has('is_recurring') ? $request->recurrence_end_date : null,
                'next_recurrence_date' => $nextRecurrence,
            ]);

            $invoice->items()->delete();
            foreach ($calculations['items'] as $row) {
                InvoiceItem::create(array_merge(['invoice_id' => $invoice->id], $row));
            }

            if ($request->hasFile('attachments')) {
                $this->uploadAttachments($request->file('attachments'), $invoice);
            }

            $this->logActivity($invoice, 'updated', "Updated Invoice");

            DB::commit();
            
            if ($request->action === 'submit') {
                return $this->submit($invoice);
            }

            return redirect()->route('invoices.index', ['type' => $invoice->type])->with('success', 'Updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    // --- Workflow Methods ---
    
    public function submit(Invoice $invoice) {
        if (!$this->userHasRole(['bookkeeper', 'admin'])) return $this->redirectUnauthorized();
        $invoice->update(['status' => 'review']);
        $this->logActivity($invoice, 'submitted', "Submitted for Review");
        return redirect()->route('invoices.index', ['type' => $invoice->type]);
    }

    public function approve(Invoice $invoice) {
        if (!$this->userHasRole(['reviewer', 'approver', 'admin'])) return $this->redirectUnauthorized();
        $invoice->update(['status' => 'reviewed']);
        $this->logActivity($invoice, 'reviewed', "Approved Invoice");
        return back()->with('success', 'Approved.');
    }

    public function reject(Invoice $invoice) {
        if (!$this->userHasRole(['reviewer', 'approver', 'admin'])) return $this->redirectUnauthorized();
        $invoice->update(['status' => 'draft']);
        $this->logActivity($invoice, 'rejected', "Rejected Invoice");
        return back()->with('success', 'Rejected.');
    }

    public function send(Invoice $invoice) {
        if (!$this->userHasRole(['approver', 'admin'])) return $this->redirectUnauthorized();
        
        try {
            DB::beginTransaction();

            $prefix = ($invoice->type === 'bill') ? 'BILL' : 'INV';
            $number = Sequence::getNextSequence($invoice->tenant_id, $prefix);

            $invoice->update(['status' => 'posted', 'number' => $number]);
            $this->logActivity($invoice, 'posted', "Posted Invoice {$number}");
            
            // Send Email Logic
            // if ($invoice->contact->email) { ... }
            
            DB::commit();
            return back()->with('success', "Invoice {$number} posted successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to post: ' . $e->getMessage());
        }
    }

    public function void(Invoice $invoice) {
        if (!$this->userHasRole(['approver', 'admin'])) return $this->redirectUnauthorized();
        $invoice->update(['status' => 'voided']);
        $this->logActivity($invoice, 'voided', "Voided Invoice");
        return back()->with('success', 'Voided.');
    }
    
    public function destroy(Invoice $invoice) {
        if (!$this->userHasRole(['bookkeeper', 'admin'])) return $this->redirectUnauthorized();
        $invoice->delete();
        return redirect()->route('invoices.index', ['type' => $invoice->type]);
    }

    public function show(Invoice $invoice)
    {
        if ($invoice->tenant_id !== auth()->user()->tenant_id) abort(403);
        $invoice->load(['items.account', 'items.taxRate', 'contact', 'attachments']);
        $isBookkeeper = $this->userHasRole(['bookkeeper', 'admin']);
        $isReviewer = $this->userHasRole(['reviewer', 'approver', 'admin']);
        $isApprover = $this->userHasRole(['approver', 'admin']);
        return view('invoices.show', compact('invoice', 'isBookkeeper', 'isReviewer', 'isApprover'));
    }

    // --- Private Calculation Helpers ---

    private function calculateInvoiceTotals($items)
    {
        $subtotal = 0;
        $taxTotal = 0;
        $processedItems = [];
        
        $taxRateIds = array_filter(array_column($items, 'tax_rate_id'));
        $dbTaxRates = TaxRate::whereIn('id', $taxRateIds)->get()->keyBy('id');

        foreach ($items as $item) {
            $qty = (float) $item['quantity'];
            $price = (float) $item['unit_price'];
            $discountRate = (float) ($item['discount_rate'] ?? 0);
            
            // 1. Calculate Gross Line
            $grossAmount = $qty * $price;

            // 2. Apply Discount
            $discountAmount = $grossAmount * ($discountRate / 100);
            $netAmount = $grossAmount - $discountAmount;
            
            // 3. Apply Tax to Net Amount
            $taxAmount = 0;
            if (!empty($item['tax_rate_id']) && isset($dbTaxRates[$item['tax_rate_id']])) {
                $rate = $dbTaxRates[$item['tax_rate_id']]->rate; 
                $taxAmount = $netAmount * $rate;
            }

            $subtotal += $netAmount; 
            $taxTotal += $taxAmount;

            $processedItems[] = [
                'account_id' => $item['account_id'],
                'tax_rate_id' => $item['tax_rate_id'] ?? null,
                'description' => $item['description'],
                'quantity' => $qty,
                'unit_price' => $price,
                'discount_rate' => $discountRate, 
                'amount' => $netAmount, 
                'tax_amount' => $taxAmount
            ];
        }

        return [
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => $subtotal + $taxTotal,
            'items' => $processedItems
        ];
    }

    private function validateInvoice($request)
    {
        $request->validate([
            'type' => ['required', Rule::in(['invoice', 'bill'])],
            'tax_type' => ['required', Rule::in(['vat', 'non_vat', 'vat_exempt', 'zero_rated'])],
            'currency_code' => ['required', 'string', 'size:3'],
            'contact_id' => ['required', Rule::exists('contacts', 'id')->where('tenant_id', auth()->user()->tenant_id)],
            'items' => 'required|array|min:1',
            'items.*.account_id' => 'required',
            'items.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_rate' => 'nullable|numeric|min:0|max:100', 
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
            $path = $file->store('invoices/' . auth()->user()->tenant_id, 's3');
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
            'ip_address' => request()->ip(),
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Contact;
use App\Models\Account;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type', 'invoice');
        
        $invoices = Invoice::with(['contact'])
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('type', $type)
            ->when($request->search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('number', 'like', "%{$search}%")
                      ->orWhere('reference', 'like', "%{$search}%");
                });
            })
            ->orderBy('date', 'desc')
            ->paginate(15);

        return view('invoices.index', compact('invoices', 'type'));
    }

    public function create(Request $request)
    {
        $type = $request->query('type', 'invoice');
        $contactType = ($type === 'invoice') ? 'customer' : 'supplier';
        
        $contacts = Contact::where('tenant_id', auth()->user()->tenant_id)
                           ->where('type', $contactType)
                           ->get()
                           ->sortBy('name'); 
        
        $accountType = ($type === 'invoice') ? 'Revenue' : 'Expense';
        $accounts = Account::where('tenant_id', auth()->user()->tenant_id)
                           ->where('type', $accountType)
                           ->orderBy('code')
                           ->get();

        return view('invoices.create', compact('type', 'contacts', 'accounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => ['required', Rule::in(['invoice', 'bill'])],
            'subtype' => ['required', Rule::in(['sales_invoice', 'service_invoice', 'standard'])],
            'tax_type' => ['required', Rule::in(['vat', 'non_vat', 'vat_exempt', 'zero_rated'])],
            'contact_id' => [
                'required', 
                Rule::exists('contacts', 'id')->where('tenant_id', auth()->user()->tenant_id)
            ],
            'items' => 'required|array|min:1',
            'items.*.account_id' => 'required',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();
            
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }
            $taxTotal = 0; 
            $grandTotal = $subtotal + $taxTotal;

            $number = null;
            if ($request->status === 'posted') {
                $prefix = 'INV-';
                if ($request->type === 'bill') {
                    $prefix = 'BILL-';
                } else {
                    if ($request->subtype === 'sales_invoice') $prefix = 'SI-';
                    elseif ($request->subtype === 'service_invoice') $prefix = 'BI-';
                }
                
                $latest = Invoice::where('tenant_id', auth()->user()->tenant_id)
                                 ->where('type', $request->type) 
                                 ->max('number'); 
                
                if ($latest && preg_match('/(\d+)$/', $latest, $matches)) {
                     $nextNum = intval($matches[1]) + 1;
                } else {
                     $nextNum = 1;
                }
                $number = $prefix . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
            }

            $invoice = Invoice::create([
                'tenant_id' => auth()->user()->tenant_id,
                'contact_id' => $request->contact_id,
                'type' => $request->type,
                'subtype' => $request->subtype,
                'tax_type' => $request->tax_type,
                'payment_terms' => $request->payment_terms,
                'number' => $number,
                'reference' => $request->reference,
                'date' => $request->date,
                'due_date' => $request->due_date,
                'status' => $request->status,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $row) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'account_id' => $row['account_id'],
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'amount' => $row['quantity'] * $row['unit_price'],
                ]);
            }

            // LOG ACTIVITY: CREATED
            $action = $request->status === 'posted' ? 'posted' : 'created';
            $docType = $request->subtype === 'sales_invoice' ? 'Sales Invoice' : ($request->type === 'bill' ? 'Bill' : 'Invoice');
            
            ActivityLog::create([
                'tenant_id' => auth()->user()->tenant_id,
                'user_id' => auth()->id(),
                'action' => $action,
                'description' => "{$action} {$docType} " . ($number ?? '(Draft)'),
                'subject_type' => Invoice::class,
                'subject_id' => $invoice->id,
                'ip_address' => $request->ip(),
                'properties' => $invoice->load('items')->toArray(), // Encrypted array
            ]);

            DB::commit();
            return redirect()->route('invoices.index', ['type' => $request->type])->with('success', 'Saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function edit(Invoice $invoice)
    {
        if ($invoice->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($invoice->status !== 'draft') {
            return redirect()->route('invoices.index', ['type' => $invoice->type])
                ->with('error', 'Only draft invoices can be edited.');
        }

        $type = $invoice->type;
        $contactType = ($type === 'invoice') ? 'customer' : 'supplier';
        
        $contacts = Contact::where('tenant_id', auth()->user()->tenant_id)
                           ->where('type', $contactType)
                           ->get()
                           ->sortBy('name'); 
        
        $accountType = ($type === 'invoice') ? 'Revenue' : 'Expense';
        $accounts = Account::where('tenant_id', auth()->user()->tenant_id)
                           ->where('type', $accountType)
                           ->orderBy('code')
                           ->get();

        $invoice->load('items');

        return view('invoices.create', compact('invoice', 'type', 'contacts', 'accounts'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Cannot edit posted invoices. Please void and recreate if necessary.');
        }

        $request->validate([
            'subtype' => ['required', Rule::in(['sales_invoice', 'service_invoice', 'standard'])],
            'tax_type' => ['required', Rule::in(['vat', 'non_vat', 'vat_exempt', 'zero_rated'])],
            'contact_id' => [
                'required', 
                Rule::exists('contacts', 'id')->where('tenant_id', auth()->user()->tenant_id)
            ],
            'items' => 'required|array|min:1',
            'items.*.account_id' => 'required',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Snapshot old state
            $oldData = $invoice->load('items')->toArray();
                        
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }
            $taxTotal = 0; 
            $grandTotal = $subtotal + $taxTotal;

            if ($request->status === 'posted' && !$invoice->number) {
                $prefix = 'INV-';
                if ($invoice->type === 'bill') {
                    $prefix = 'BILL-';
                } else {
                    if ($request->subtype === 'sales_invoice') $prefix = 'SI-';
                    elseif ($request->subtype === 'service_invoice') $prefix = 'BI-';
                }
                
                $latest = Invoice::where('tenant_id', auth()->user()->tenant_id)
                                 ->where('type', $invoice->type) 
                                 ->max('number'); 
                
                if ($latest && preg_match('/(\d+)$/', $latest, $matches)) {
                     $nextNum = intval($matches[1]) + 1;
                } else {
                     $nextNum = 1;
                }
                $invoice->number = $prefix . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
            }

            $invoice->update([
                'contact_id' => $request->contact_id,
                'subtype' => $request->subtype,
                'tax_type' => $request->tax_type,
                'payment_terms' => $request->payment_terms,
                'reference' => $request->reference,
                'date' => $request->date,
                'due_date' => $request->due_date,
                'status' => $request->status,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'notes' => $request->notes,
            ]);

            $invoice->items()->delete();
            foreach ($request->items as $row) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'account_id' => $row['account_id'],
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'amount' => $row['quantity'] * $row['unit_price'],
                ]);
            }

            // LOG ACTIVITY: UPDATED / POSTED
            $action = ($request->status === 'posted' && $oldData['status'] === 'draft') ? 'posted' : 'updated';
            $docType = $invoice->subtype === 'sales_invoice' ? 'Sales Invoice' : 'Invoice';
            $desc = $action === 'posted' 
                ? "Posted {$docType} {$invoice->number}" 
                : "Updated {$docType} " . ($invoice->number ?? '(Draft)');

            ActivityLog::create([
                'tenant_id' => auth()->user()->tenant_id,
                'user_id' => auth()->id(),
                'action' => $action,
                'description' => $desc,
                'subject_type' => Invoice::class,
                'subject_id' => $invoice->id,
                'ip_address' => $request->ip(),
                'properties' => [
                    'old' => $oldData,
                    'new' => $invoice->fresh()->load('items')->toArray()
                ],
            ]);

            DB::commit();
            return redirect()->route('invoices.index', ['type' => $invoice->type])->with('success', 'Updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Posted invoices cannot be deleted as they have an official series number. Please use Void instead.');
        }

        $number = $invoice->number ?? 'Draft';
        // Snapshot before delete
        $snapshot = $invoice->load('items')->toArray();

        $invoice->delete();
        
        // LOG ACTIVITY: DELETED
        ActivityLog::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'action' => 'deleted',
            'description' => "Deleted invoice {$number}",
            'subject_type' => Invoice::class,
            'subject_id' => $invoice->id,
            'ip_address' => request()->ip(),
            'properties' => $snapshot,
        ]);
        
        
        return redirect()->route('invoices.index', ['type' => $invoice->type])->with('success', 'Draft deleted successfully.');
    }

    public function void(Invoice $invoice)
    {
        if ($invoice->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
        
        if (!in_array($invoice->status, ['posted', 'paid'])) {
             return back()->with('error', 'Only posted or paid invoices can be voided.');
        }

        $invoice->update(['status' => 'voided']);
        
        ActivityLog::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'action' => 'voided',
            'description' => "Voided invoice {$invoice->number}",
            'subject_type' => Invoice::class,
            'subject_id' => $invoice->id,
            'ip_address' => request()->ip(),
            'properties' => $invoice->load('items')->toArray(),
        ]);
        
        return back()->with('success', 'Invoice voided successfully.');
    }


    /**
     * Display the specified resource.
     */    
    public function show(Invoice $invoice)
    {
        // Ensure user owns this invoice
        if ($invoice->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $invoice->load(['items.account', 'contact']); // Eager load relationships
        return view('invoices.show', compact('invoice'));
    }    
}
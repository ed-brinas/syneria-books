<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Sequence; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class JournalEntryController extends Controller
{
    public function index(Request $request)
    {
        $query = JournalEntry::with('lines.account')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        // Search Logic
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
                if (is_numeric($search)) {
                    $q->orWhereHas('lines', function($lineQ) use ($search) {
                        $lineQ->where('debit', $search)
                              ->orWhere('credit', $search);
                    });
                }
            });
        }

        $entries = $query->paginate(20);
            
        return view('journals.index', compact('entries'));
    }

    public function create()
    {
        $accounts = Account::active()->orderBy('code')->get();
        return view('journals.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:500',
            'status' => 'required|in:draft,posted', 
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => ['required', 'exists:accounts,id'],
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
        ]);

        $lines = $request->input('lines');
        
        $totalDebit = collect($lines)->sum('debit');
        $totalCredit = collect($lines)->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.0001) {
            throw ValidationException::withMessages(['lines' => 'Journal entry is not balanced. Debits must equal Credits.']);
        }

        DB::beginTransaction();

        try {
            // Logic 1: Drafts vs Posted
            $reference = null;
            $status = $request->status;

            if ($status === 'posted') {
                $reference = Sequence::getNextSequence($tenantId, 'JV');
            }

            // 2. Create Header
            $entry = new JournalEntry();
            $entry->tenant_id = $tenantId;
            $entry->date = $request->date;
            $entry->reference = $reference; 
            $entry->description = $request->description;
            $entry->status = $status; 
            $entry->created_by = Auth::id();
            $entry->locked = ($status === 'posted'); 
            $entry->save();

            // 3. Create Lines
            foreach ($lines as $lineData) {
                $this->createLine($entry, $lineData, $tenantId);
            }

            // Activity Log
            $action = $status === 'posted' ? 'posted' : 'created';
            $logDesc = $status === 'posted' 
                ? "Posted Journal Entry {$reference}" 
                : "Created Draft Journal Entry";

            ActivityLog::create([
                'tenant_id' => $tenantId,
                'user_id' => Auth::id(),
                'action' => $action,
                'description' => $logDesc,
                'subject_type' => JournalEntry::class,
                'subject_id' => $entry->id,
                'ip_address' => $request->ip(),
                'properties' => [ // Removed json_encode
                    'total_amount' => $totalDebit,
                    'line_count' => count($lines)
                ],
            ]);

            DB::commit();
            
            $message = $status === 'posted' 
                ? "Journal Entry {$reference} Posted Successfully." 
                : "Journal Entry saved as Draft.";

            return redirect()->route('journals.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Transaction failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(JournalEntry $journal)
    {
        if ($journal->tenant_id !== auth()->user()->tenant_id) abort(403);

        // Load relationships needed for the PDF view
        $journal->load(['lines.account', 'creator']);

        return view('journals.show', compact('journal'));
    }    

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(JournalEntry $journal)
    {
        if ($journal->tenant_id !== auth()->user()->tenant_id) abort(403);

        if ($journal->status !== 'draft') {
            return redirect()->route('journals.index')->withErrors(['error' => 'Only draft entries can be edited.']);
        }

        $accounts = Account::active()->orderBy('code')->get();

        // Convert lines object to array for the view
        $lines = $journal->lines->map(function ($line) {
            return [
                'account_id' => $line->account_id,
                'description' => $line->description,
                'debit' => $line->debit,
                'credit' => $line->credit,
            ];
        })->toArray();

        return view('journals.create', [
            'accounts' => $accounts,
            'journal' => $journal, // Passes the model to indicate Edit Mode
            'prefill' => [
                'date' => $journal->date->format('Y-m-d'),
                'reference' => $journal->reference,
                'description' => $journal->description,
                'lines' => $lines
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, JournalEntry $journal)
    {
        $tenantId = auth()->user()->tenant_id;
        if ($journal->tenant_id !== $tenantId) abort(403);

        if ($journal->status !== 'draft') {
            return redirect()->route('journals.index')->withErrors(['error' => 'Cannot edit a posted or voided entry.']);
        }

        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:500',
            'status' => 'required|in:draft,posted', 
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => ['required', 'exists:accounts,id'],
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
        ]);

        $lines = $request->input('lines');
        
        $totalDebit = collect($lines)->sum('debit');
        $totalCredit = collect($lines)->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.0001) {
            throw ValidationException::withMessages(['lines' => 'Journal entry is not balanced.']);
        }

        DB::beginTransaction();

        try {
            $status = $request->status;
            $reference = $journal->reference;

            // If changing from Draft to Posted, generate Reference
            if ($status === 'posted' && $journal->status === 'draft') {
                $reference = Sequence::getNextSequence($tenantId, 'JV');
            }

            // 1. Update Header
            $journal->update([
                'date' => $request->date,
                'reference' => $reference,
                'description' => $request->description,
                'status' => $status,
                'locked' => ($status === 'posted'),
            ]);

            // 2. Sync Lines (Delete all old, create new)
            $journal->lines()->delete();

            foreach ($lines as $lineData) {
                $this->createLine($journal, $lineData, $tenantId);
            }

            // Activity Log
            $action = ($status === 'posted' && $oldStatus === 'draft') ? 'posted' : 'updated';
            $logDesc = ($action === 'posted')
                ? "Posted previously drafted Journal Entry {$reference}"
                : "Updated Draft Journal Entry";

            ActivityLog::create([
                'tenant_id' => $tenantId,
                'user_id' => Auth::id(),
                'action' => $action,
                'description' => $logDesc,
                'subject_type' => JournalEntry::class,
                'subject_id' => $journal->id,
                'ip_address' => $request->ip(),
                'properties' => [ // Removed json_encode
                    'total_amount' => $totalDebit,
                    'line_count' => count($lines)
                ],
            ]);

            DB::commit();

            $message = $status === 'posted' 
                ? "Journal Entry {$reference} Posted Successfully." 
                : "Journal Entry updated.";

            return redirect()->route('journals.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Update failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Logic 2 Option A: Voiding
     */
    public function void(JournalEntry $journal)
    {
        if ($journal->tenant_id !== auth()->user()->tenant_id) abort(403);

        if ($journal->status !== 'posted') {
            return back()->withErrors(['error' => 'Only posted entries can be voided.']);
        }

        $journal->update([
            'status' => 'voided',
            'description' => $journal->description . ' [VOIDED]',
        ]);

        // Activity Log
        ActivityLog::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => Auth::id(),
            'action' => 'voided',
            'description' => "Voided Journal Entry {$journal->reference}",
            'subject_type' => JournalEntry::class,
            'subject_id' => $journal->id,
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', 'Journal Entry has been voided.');
    }

    /**
     * Logic 2 Option B: Reversing
     */
    public function reverse(JournalEntry $journal)
    {
        if ($journal->tenant_id !== auth()->user()->tenant_id) abort(403);

        $accounts = Account::active()->orderBy('code')->get();
        
        $reversedLines = $journal->lines->map(function ($line) {
            return [
                'account_id' => $line->account_id,
                'description' => 'Reversal: ' . $line->description,
                'debit' => $line->credit,  
                'credit' => $line->debit, 
            ];
        });

        return view('journals.create', [
            'accounts' => $accounts,
            'prefill' => [
                'date' => date('Y-m-d'),
                'description' => "Reversal of " . ($journal->reference ?? 'JV'),
                'lines' => $reversedLines
            ]
        ]);
    }

    /**
     * Delete: Only allowed for Drafts
     */
    public function destroy(JournalEntry $journal)
    {
        if ($journal->tenant_id !== auth()->user()->tenant_id) abort(403);

        if ($journal->status === 'posted' || $journal->locked) {
            return back()->withErrors(['error' => 'Cannot delete a Posted entry. Please Void it instead.']);
        }

        $journal->delete();

        // Activity Log
        ActivityLog::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'description' => "Deleted Draft Journal Entry: {$desc}",
            'subject_type' => JournalEntry::class,
            'subject_id' => $id, // ID might technically not exist in DB anymore, but useful for ref
            'ip_address' => request()->ip(),
        ]);       

        return back()->with('success', 'Draft entry deleted.');
    }

    /**
     * Helper to create lines (used in store and update)
     */
    private function createLine($entry, $lineData, $tenantId)
    {
        $account = Account::find($lineData['account_id']);
        
        if (!$account || $account->tenant_id !== $tenantId) {
            throw new \Exception("Security Violation: Invalid Account ID detected.");
        }

        $line = new JournalEntryLine();
        $line->tenant_id = $tenantId;
        $line->journal_entry_id = $entry->id;
        $line->account_id = $lineData['account_id'];
        $line->debit = $lineData['debit'];
        $line->credit = $lineData['credit'];
        $line->description = $lineData['description'] ?? null;
        $line->save();
    }
}
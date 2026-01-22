<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Sequence; 
use App\Models\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class JournalEntryController extends Controller
{
    /**
     * Helper: Check User Role
     */
    private function userHasRole(array $allowedRoles)
    {
        $userRole = strtolower(Auth::user()->role ?? '');
        return in_array($userRole, array_map('strtolower', $allowedRoles));
    }

    /**
     * Helper: Redirect with Error Modal
     */
    private function redirectUnauthorized($message = 'You are not authorized to perform this action.')
    {
        return redirect()->route('journals.index')->with('error_modal', $message);
    }

    public function index(Request $request)
    {
        $query = JournalEntry::with('lines.account', 'creator')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        // --- Filter by Status ---
        if ($request->has('status') && $request->status !== 'all' && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // --- Search ---
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
        
        // Append current query parameters to pagination links
        $entries->appends($request->all());

        // Pass Role Permission to View
        $isApprover = $this->userHasRole(['approver']);

        return view('journals.index', compact('entries', 'isApprover'));
    }

    public function create()
    {
        if (!$this->userHasRole(['bookkeeper'])) {
            return $this->redirectUnauthorized('Role must be Bookkeeper to create entries.');
        }

        $accounts = Account::active()->orderBy('code')->get();
        
        $taxRates = TaxRate::where('tenant_id', auth()->user()->tenant_id)
            ->active()
            ->orderBy('rate', 'desc')
            ->get();
        
        return view('journals.create', compact('accounts', 'taxRates'));
    }

    public function store(Request $request)
    {
        if (!$this->userHasRole(['bookkeeper'])) {
            return $this->redirectUnauthorized('Only Bookkeepers can create Journal Entries.');
        }

        $tenantId = auth()->user()->tenant_id;

        $request->validate([
            'date' => 'required|date',
            'auto_reverse_date' => 'nullable|date|after:date', 
            'tax_type' => 'nullable|string|in:no_tax,inclusive,exclusive', 
            'description' => 'required|string|max:500',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => ['required', 'exists:accounts,id'],
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.tax_code_id' => 'nullable|exists:tax_rates,id', 
            'lines.*.tax_amount' => 'nullable|numeric|min:0',
        ]);

        $lines = $request->input('lines');
        $this->validateBalance($lines);

        DB::beginTransaction();

        try {
            $targetStatus = $request->input('action') === 'submit' ? 'review' : 'draft';

            $entry = new JournalEntry();
            $entry->tenant_id = $tenantId;
            $entry->date = $request->date;
            $entry->auto_reverse_date = $request->auto_reverse_date;
            $entry->tax_type = $request->tax_type ?? 'no_tax';       
            $entry->reference = null;
            $entry->description = $request->description;
            $entry->status = $targetStatus; 
            $entry->created_by = Auth::id();
            $entry->locked = false; 
            $entry->save();

            foreach ($lines as $lineData) {
                $this->createLine($entry, $lineData, $tenantId);
            }

            $actionMsg = $targetStatus === 'review' ? 'Created and Submitted for Review' : 'Created Draft Journal Entry';
            $this->logActivity($entry, 'created', $actionMsg, $lines);

            DB::commit();

            return redirect()->route('journals.index')->with('success', "Journal Entry saved as {$targetStatus}.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Transaction failed: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, JournalEntry $journal)
    {
        if ($journal->tenant_id !== auth()->user()->tenant_id) {
            return $this->redirectUnauthorized('Unauthorized access to this journal.');
        }
        
        if ($journal->status === 'draft' && !$this->userHasRole(['bookkeeper'])) {
             return $this->redirectUnauthorized('Only Bookkeepers can edit Drafts.');
        }
        if ($journal->status === 'review' && !$this->userHasRole(['bookkeeper', 'reviewer'])) {
             return $this->redirectUnauthorized('Only Bookkeepers or Reviewers can edit entries in Review.');
        }
        
        // Modified: Check for 'reviewed' instead of 'approved'
        if (in_array($journal->status, ['reviewed', 'posted', 'voided'])) {
            return redirect()->route('journals.index')->with('error_modal', 'Cannot edit a Reviewed or Posted entry.');
        }

        $request->validate([
            'date' => 'required|date',
            'auto_reverse_date' => 'nullable|date|after:date',
            'tax_type' => 'nullable|string|in:no_tax,inclusive,exclusive',
            'description' => 'required|string|max:500',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => ['required', 'exists:accounts,id'],
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.tax_code_id' => 'nullable|exists:tax_rates,id',
            'lines.*.tax_amount' => 'nullable|numeric|min:0',
        ]);

        $lines = $request->input('lines');
        $this->validateBalance($lines);

        DB::beginTransaction();
        try {
            $targetStatus = $journal->status;
            if ($request->input('action') === 'submit') {
                $targetStatus = 'review';
            }

            $journal->update([
                'date' => $request->date,
                'auto_reverse_date' => $request->auto_reverse_date,
                'tax_type' => $request->tax_type ?? 'no_tax',
                'description' => $request->description,
                'status' => $targetStatus
            ]);

            $journal->lines()->delete();
            foreach ($lines as $lineData) {
                $this->createLine($journal, $lineData, auth()->user()->tenant_id);
            }

            $this->logActivity($journal, 'updated', "Updated Journal Entry ({$targetStatus})", $lines);

            DB::commit();
            return redirect()->route('journals.index')->with('success', 'Journal Entry updated.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Update failed: ' . $e->getMessage()]);
        }
    }

    public function submit(JournalEntry $journal)
    {
        if (!$this->userHasRole(['bookkeeper'])) {
            return $this->redirectUnauthorized('Only Bookkeepers can submit drafts for review.');
        }
        
        if ($journal->status !== 'draft') {
            return redirect()->route('journals.index')->with('error_modal', 'Entry must be Draft to submit.');
        }
        
        $journal->update(['status' => 'review']);
        $this->logActivity($journal, 'submitted', 'Submitted Journal for Review');

        return back()->with('success', 'Journal submitted for review.');
    }

    // --- Workflow: Step 2 (Reviewer Approves -> Reviewed) ---
    public function approve(JournalEntry $journal)
    {
        if (!$this->userHasRole(['reviewer', 'approver'])) {
            return $this->redirectUnauthorized('Only Reviewers or Approvers can approve entries.');
        }

        if ($journal->status !== 'review') {
             return redirect()->route('journals.index')->with('error_modal', 'Entry must be in Review to approve.');
        }

        // Modified: Set status to 'reviewed' (intermediate state)
        $journal->update([
            'status' => 'reviewed',
        ]);

        $this->logActivity($journal, 'reviewed', "Journal marked as Reviewed");

        return back()->with('success', "Journal marked as Reviewed. Waiting for Posting.");
    }

    // --- Workflow: Rejection (Send back to Draft) ---
    public function reject(JournalEntry $journal)
    {
        if (!$this->userHasRole(['reviewer', 'approver'])) {
            return $this->redirectUnauthorized('Only Reviewers or Approvers can reject entries.');
        }

        // Modified: Can reject from 'review' OR 'reviewed'
        if (!in_array($journal->status, ['review', 'reviewed'])) {
            return redirect()->route('journals.index')->with('error_modal', 'Only entries in Review or Reviewed status can be rejected.');
        }

        $previousStatus = ucfirst($journal->status);
        
        $journal->update([
            'status' => 'draft',
            'locked' => false, 
        ]);

        $this->logActivity($journal, 'rejected', "Rejected from {$previousStatus} to Draft");

        return back()->with('success', "Journal rejected and returned to Draft for corrections.");
    }

    // --- Workflow: Step 3 (Approver Posts -> Posted) ---
    public function post(JournalEntry $journal)
    {
        if (!$this->userHasRole(['approver'])) {
            return $this->redirectUnauthorized('Only Approvers can post entries.');
        }

        // Modified: Entry must be 'reviewed' to post
        if ($journal->status !== 'reviewed') {
            return redirect()->route('journals.index')->with('error_modal', 'Entry must be Reviewed before Posting.');
        }

        DB::beginTransaction();
        try {
            $reference = Sequence::getNextSequence($journal->tenant_id, 'JV');

            $journal->update([
                'status' => 'posted',
                'reference' => $reference,
                'locked' => true, 
            ]);
            
            $this->logActivity($journal, 'posted', "Posted Journal Entry {$reference}");

            DB::commit();
            return back()->with('success', "Journal Entry Posted to General Ledger. Reference {$reference} generated.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(JournalEntry $journal)
    {
        if ($journal->tenant_id !== auth()->user()->tenant_id) {
             return $this->redirectUnauthorized('Unauthorized access to this journal.');
        }
        
        $journal->load(['lines.account', 'creator']);
        
        $activities = ActivityLog::where('subject_type', JournalEntry::class)
            ->where('subject_id', $journal->id)
            ->with('user')
            ->latest()
            ->get();
            
        $journal->setRelation('activities', $activities);
        
        return view('journals.show', compact('journal'));
    }

    public function edit(JournalEntry $journal)
    {
        if ($journal->tenant_id !== auth()->user()->tenant_id) {
             return $this->redirectUnauthorized('Unauthorized access to this journal.');
        }

        if (!in_array($journal->status, ['draft', 'review'])) {
            return redirect()->route('journals.index')->with('error_modal', 'Only Draft or Review entries can be edited.');
        }

        if ($journal->status === 'draft' && !$this->userHasRole(['bookkeeper'])) {
             return $this->redirectUnauthorized('Unauthorized.');
        }
        if ($journal->status === 'review' && !$this->userHasRole(['bookkeeper', 'reviewer'])) {
             return $this->redirectUnauthorized('Unauthorized.');
        }

        $accounts = Account::active()->orderBy('code')->get();
        
        $taxRates = TaxRate::where('tenant_id', auth()->user()->tenant_id)
            ->active()
            ->orderBy('rate', 'desc')
            ->get();

        $lines = $journal->lines->map(function ($line) {
            return [
                'account_id' => $line->account_id,
                'description' => $line->description,
                'debit' => $line->debit,
                'credit' => $line->credit,
                'tax_code_id' => $line->tax_code_id,
                'tax_amount' => $line->tax_amount,
            ];
        })->toArray();

        return view('journals.create', [
            'accounts' => $accounts,
            'taxRates' => $taxRates,
            'journal' => $journal,
            'prefill' => [
                'date' => $journal->date->format('Y-m-d'),
                'auto_reverse_date' => $journal->auto_reverse_date ? $journal->auto_reverse_date->format('Y-m-d') : null,
                'tax_type' => $journal->tax_type,
                'reference' => $journal->reference,
                'description' => $journal->description,
                'lines' => $lines
            ]
        ]);
    }

    public function void(JournalEntry $journal)
    {
        if (!$this->userHasRole(['approver'])) {
            return $this->redirectUnauthorized('Only Approvers can void entries.');
        }
        
        if ($journal->status !== 'posted') {
            return back()->withErrors(['error' => 'Only posted entries can be voided.']);
        }
        $journal->update(['status' => 'voided', 'description' => $journal->description . ' [VOIDED]']);
        $this->logActivity($journal, 'voided', "Voided Journal Entry {$journal->reference}");
        return back()->with('success', 'Journal Entry has been voided.');
    }

    public function reverse(JournalEntry $journal)
    {
        if ($journal->tenant_id !== auth()->user()->tenant_id) {
             return $this->redirectUnauthorized('Unauthorized access.');
        }
        $accounts = Account::active()->orderBy('code')->get();
        $taxRates = TaxRate::where('tenant_id', auth()->user()->tenant_id)->active()->get();

        $reversedLines = $journal->lines->map(function ($line) {
            return [
                'account_id' => $line->account_id,
                'description' => 'Reversal: ' . $line->description,
                'debit' => $line->credit,  
                'credit' => $line->debit, 
                'tax_code_id' => $line->tax_code_id,
                'tax_amount' => $line->tax_amount, 
            ];
        });
        return view('journals.create', [
            'accounts' => $accounts,
            'taxRates' => $taxRates,
            'prefill' => [
                'date' => date('Y-m-d'),
                'description' => "Reversal of " . ($journal->reference ?? 'JV'),
                'lines' => $reversedLines
            ]
        ]);
    }

    public function destroy(JournalEntry $journal)
    {
        if ($journal->tenant_id !== auth()->user()->tenant_id) {
             return $this->redirectUnauthorized();
        }
        
        if ($journal->status !== 'draft') {
            return redirect()->route('journals.index')->with('error_modal', 'Only Draft entries can be deleted.');
        }
        
        if (!$this->userHasRole(['bookkeeper'])) {
             return $this->redirectUnauthorized('Unauthorized.');
        }

        $journal->delete();
        return back()->with('success', 'Draft entry deleted.');
    }

    private function validateBalance($lines)
    {
        $totalDebit = collect($lines)->sum('debit');
        $totalCredit = collect($lines)->sum('credit');
        if (abs($totalDebit - $totalCredit) > 0.0001) {
            throw ValidationException::withMessages(['lines' => 'Journal entry is not balanced. Debits must equal Credits.']);
        }
    }

    private function createLine($entry, $lineData, $tenantId)
    {
        $account = Account::find($lineData['account_id']);
        if (!$account || $account->tenant_id !== $tenantId) throw new \Exception("Invalid Account ID.");

        $line = new JournalEntryLine();
        $line->tenant_id = $tenantId;
        $line->journal_entry_id = $entry->id;
        $line->account_id = $lineData['account_id'];
        $line->debit = $lineData['debit'];
        $line->credit = $lineData['credit'];
        $line->description = $lineData['description'] ?? null;
        
        $line->tax_code_id = $lineData['tax_code_id'] ?? null;
        $line->tax_amount = $lineData['tax_amount'] ?? 0;
        
        $line->save();
    }

    private function logActivity($entry, $action, $desc, $lines = [])
    {
        ActivityLog::create([
            'tenant_id' => $entry->tenant_id,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $desc,
            'subject_type' => JournalEntry::class,
            'subject_id' => $entry->id,
            'ip_address' => request()->ip(),
            'properties' => $lines ? ['line_count' => count($lines)] : []
        ]);
    }
}
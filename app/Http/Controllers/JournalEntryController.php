<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Account;
use App\Models\Sequence; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class JournalEntryController extends Controller
{
    public function index()
    {
        $entries = JournalEntry::with('lines.account')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
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
            // 1. Auto-Generate BIR Compliant Reference
            // CHANGED: Static call to the Model
            $reference = Sequence::getNextSequence($tenantId, 'JV');

            // 2. Create Header
            $entry = new JournalEntry();
            $entry->tenant_id = $tenantId;
            $entry->date = $request->date;
            $entry->reference = $reference; 
            $entry->description = $request->description;
            $entry->status = 'posted'; 
            $entry->created_by = Auth::id();
            $entry->save();

            // 3. Create Lines
            foreach ($lines as $lineData) {
                $account = Account::find($lineData['account_id']);
                // Strict Tenant Check
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

            DB::commit();
            
            return redirect()->route('journals.index')
                ->with('success', "Journal Entry {$reference} Posted Successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Transaction failed: ' . $e->getMessage()]);
        }
    }
}
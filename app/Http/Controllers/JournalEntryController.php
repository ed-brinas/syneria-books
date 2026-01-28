<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Sequence; 
use App\Models\TaxRate;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class JournalEntryController extends Controller
{
    private function userHasRole(array $allowedRoles)
    {
        $userRole = strtolower(Auth::user()->role ?? '');
        return in_array($userRole, array_map('strtolower', $allowedRoles));
    }

    private function redirectUnauthorized($message = 'Unauthorized action.')
    {
        return back()->with('error', $message);
    }

    public function index(Request $request)
    {
        $query = JournalEntry::with(['lines.account', 'creator', 'branch'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        // --- Filter by Status ---
        if ($request->has('status') && $request->status !== 'all' && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // --- Filter by Branch (Strict Enforcement) ---
        // If user filters by branch, use it. 
        // Otherwise, default to the User's "Current Branch" session if available, or Main Branch.
        // For now, we allow "All Branches" view for Admins/CFOs, but UI should encourage filtering.
        if ($request->has('branch_id') && !empty($request->branch_id)) {
            $query->where('branch_id', $request->branch_id);
        }

        // --- Search ---
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $entries = $query->paginate(15);
        
        // Pass branches for the filter dropdown in the view
        $branches = Auth::user()->tenant->branches()->orderBy('code')->get();

        return view('journals.index', compact('entries', 'branches'));
    }

    public function create()
    {
        $accounts = Account::where('is_active', true)->orderBy('code')->get();
        // Only active branches can create new entries
        $branches = Branch::where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return view('journals.create', compact('accounts', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:500',
            'reference' => 'nullable|string|max:50',
            
            // Branch ID is mandatory to ensure "One Entry = One Branch" rule
            'branch_id' => 'required|exists:branches,id',
            
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
        ]);

        $this->validateBalance($request->lines);

        $tenant = Auth::user()->tenant;
        
        // Ensure Branch belongs to Tenant
        $branch = Branch::where('id', $request->branch_id)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        DB::transaction(function () use ($request, $tenant, $branch) {
            $entry = JournalEntry::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id, // Locked to specific branch
                'date' => $request->date,
                'description' => $request->description,
                'reference' => $request->reference,
                'status' => $request->status ?? 'posted', 
                'created_by' => Auth::id(),
                'locked' => false,
            ]);

            foreach ($request->lines as $lineData) {
                $this->createLine($entry, $lineData, $tenant->id);
            }

            $this->logActivity($entry, 'created', "Created Journal Entry: {$request->reference} for Branch {$branch->code}", $request->lines);
        });

        return redirect()->route('journals.index')->with('success', 'Journal Entry created successfully.');
    }

    public function show(JournalEntry $journalEntry)
    {
        if ($journalEntry->tenant_id !== Auth::user()->tenant_id) abort(403);
        $journalEntry->load(['lines.account', 'creator', 'branch']);
        return view('journals.show', compact('journalEntry'));
    }

    // --- Helpers ---

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
            'properties' => ['lines_count' => count($lines)]
        ]);
    }
}
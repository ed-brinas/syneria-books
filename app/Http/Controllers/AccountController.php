<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Tenant scope is applied automatically via BelongsToTenant trait
        $accounts = Account::orderBy('code')->get();
        return view('accounts.index', compact('accounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => [
                'required', 
                'string', 
                'max:20',
                Rule::unique('accounts')->where(function ($query) {
                    return $query->where('tenant_id', auth()->user()->tenant_id);
                })
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'subtype' => 'nullable|string|max:255',
            'description' => 'nullable|string'
        ]);

        $account = new Account($validated);
        $account->tenant_id = auth()->user()->tenant_id; 
        $account->save();

        // Activity Log
        ActivityLog::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'action' => 'created',
            'description' => "Created Account: {$account->code} - {$account->name}",
            'subject_type' => Account::class,
            'subject_id' => $account->id,
            'ip_address' => $request->ip(),
            'properties' => $account->toArray(),
        ]);

        return redirect()->route('accounts.index')->with('success', 'Account created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Account $account)
    {
        // Security Check: Ensure account belongs to tenant
        if ($account->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        // 1. Strict Rule: Cannot edit system accounts
        if ($account->is_system) {
             return back()->withErrors(['error' => 'System accounts cannot be edited.']);
        }

        $validated = $request->validate([
            'code' => [
                'required', 
                'string', 
                'max:20',
                Rule::unique('accounts')->ignore($account->id)->where(function ($query) {
                    return $query->where('tenant_id', auth()->user()->tenant_id);
                })
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'subtype' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean' // Allow reactivation via edit if needed
        ]);

        $account->update($validated);

        // Activity Log
        ActivityLog::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'action' => 'updated',
            'description' => "Updated Account: {$account->code} - {$account->name}",
            'subject_type' => Account::class,
            'subject_id' => $account->id,
            'ip_address' => $request->ip(),
            'properties' =>[
                'old' => $originalData,
                'new' => $account->fresh()->toArray()
            ],
        ]);

        return redirect()->route('accounts.index')->with('success', 'Account updated successfully.');
    }

    /**
     * Remove (Deactivate) the specified resource from storage.
     */
    public function destroy(Account $account)
    {
        // Security Check
        if ($account->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        // 1. Strict Rule: Cannot delete system accounts
        if ($account->is_system) {
            return back()->withErrors(['error' => 'System accounts cannot be deleted.']);
        }

        // 2. Soft Delete Logic: Set is_active to 0
        $account->update(['is_active' => false]);

        // Activity Log
        ActivityLog::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'action' => 'deactivated',
            'description' => "Deactivated Account: {$account->code} - {$account->name}",
            'subject_type' => Account::class,
            'subject_id' => $account->id,
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('accounts.index')->with('success', 'Account deactivated successfully.');
    }
}
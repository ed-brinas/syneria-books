<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BankAccount;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Currencies; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrganizationController extends Controller
{
    public function index()
    {
        $countries = Countries::getNames();
        $currencies = Currencies::getNames();
        $tenant = auth()->user()->tenant; 
        
        // Load ONLY active bank accounts
        $tenant->load(['bankAccounts' => function($query) {
            $query->where('is_active', true);
        }]);

        // Define Business Types Logic
        $businessTypesPH = [
            'Sole Proprietorship' => 'Sole Proprietorship',
            'Partnership' => 'Partnership',
            'Corporation' => 'Corporation',
            'OPC' => 'One Person Corporation (OPC)',
            'Cooperative' => 'Cooperative',
            'Foundation' => 'Foundation / Non-Stock Non-Profit',
        ];

        $businessTypesIntl = [
            'Sole Proprietorship' => 'Sole Proprietorship / Sole Trader',
            'Partnership' => 'Partnership / LLP',
            'LLC' => 'LLC / Ltd',
            'Corporation' => 'Corporation / Pty Ltd',
            'Non-Profit' => 'Non-Profit / Charity',
        ];

        return view('settings.organization.index', compact('tenant','countries','currencies', 'businessTypesPH', 'businessTypesIntl'));
    }

    public function update(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $originalData = $tenant->toArray();

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:50'],
            'company_reg_number' => ['nullable', 'string', 'max:255'],
            'tax_identification_number' => ['nullable', 'string', 'max:255'],
            'business_address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
        ]);

        $tenant->update($validated);

        ActivityLog::create([
            'tenant_id' => Auth::user()->tenant_id,
            'user_id' => Auth::id(),
            'action' => 'updated',
            'description' => "Updated Organization Profile for {$tenant->company_name}",
            'subject_type' => get_class($tenant),
            'subject_id' => $tenant->id,
            'ip_address' => $request->ip(),
            'properties' => [
                'old' => array_intersect_key($originalData, $validated),
                'new' => $tenant->only(array_keys($validated)),
            ],
        ]);

        return back()->with('success', 'Organization details updated successfully.');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048', 'dimensions:max_width=1000,max_height=1000'],
        ]);

        $tenant = auth()->user()->tenant;
        $oldLogo = $tenant->logo_path;

        if ($request->hasFile('photo')) {
            if ($tenant->logo_path) {
                Storage::disk('s3')->delete($tenant->logo_path);
            }
            $path = $request->file('photo')->storePublicly('accounting-organization-photos', 's3');          
            $tenant->logo_path = $path;
            $tenant->update(['logo_path' => $path]);

            ActivityLog::create([
                'tenant_id' => Auth::user()->tenant_id,
                'user_id' => Auth::id(),
                'action' => 'uploaded_logo',
                'description' => "Uploaded new organization logo",
                'subject_type' => get_class($tenant),
                'subject_id' => $tenant->id,
                'ip_address' => $request->ip(),
                'properties' => ['old_logo_path' => $oldLogo, 'new_logo_path' => $path],
            ]);
        }

        return back()->with('success', 'Logo uploaded successfully.');
    }

    /**
     * Store a new bank account with Named Error Bag ('addBank').
     */
    public function storeBank(Request $request)
    {
        // Use Validator::make to specify a named error bag "addBank"
        $validator = Validator::make($request->all(), [
            // Bank Account Details
            'bank_name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'currency' => ['required', 'string', 'size:3'],
            'branch_code' => ['nullable', 'string', 'max:255'],
            'swift_code' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],

            // Chart of Accounts Details (Manual Input)
            'coa_code' => [
                'required', 
                'string', 
                'max:20',
                Rule::unique('accounts', 'code')->where(function ($query) {
                    return $query->where('tenant_id', auth()->user()->tenant_id);
                })
            ],
            'coa_name' => ['required', 'string', 'max:255'],
            'coa_type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'coa_subtype' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'addBank')->withInput();
        }

        $validated = $validator->validated();
        $tenant = auth()->user()->tenant;

        DB::transaction(function () use ($validated, $tenant, $request) {
            
            // 1. Create Linked COA
            $account = Account::create([
                'tenant_id' => $tenant->id,
                'code' => $validated['coa_code'],
                'name' => $validated['coa_name'],
                'type' => $validated['coa_type'],
                'subtype' => $validated['coa_subtype'],
                'description' => 'Linked to Bank Account: ' . $validated['account_number'],
                'is_active' => true,
                'is_system' => false,
            ]);

            // Activity Log (COA)
            ActivityLog::create([
                'tenant_id' => Auth::user()->tenant_id,
                'user_id' => Auth::id(),
                'action' => 'created',
                'description' => "Created Account (COA) for Bank: {$account->code} - {$account->name}",
                'subject_type' => Account::class,
                'subject_id' => $account->id,
                'ip_address' => $request->ip(),
                'properties' => $account->toArray(),
            ]);

            // 2. Create Bank Account
            $bankAccount = $tenant->bankAccounts()->create([
                'bank_name' => $validated['bank_name'],
                'account_name' => $validated['account_name'],
                'account_number' => $validated['account_number'],
                'currency' => $validated['currency'],
                'branch_code' => $validated['branch_code'] ?? null,
                'swift_code' => $validated['swift_code'] ?? null,
                'address' => $validated['address'] ?? null,
                'account_id' => $account->id,
                'is_active' => true,
            ]);

            // Activity Log (Bank)
            ActivityLog::create([
                'tenant_id' => Auth::user()->tenant_id,
                'user_id' => Auth::id(),
                'action' => 'created',
                'description' => "Added Bank Account: {$bankAccount->bank_name} - {$bankAccount->account_number}",
                'subject_type' => BankAccount::class,
                'subject_id' => $bankAccount->id,
                'ip_address' => $request->ip(),
                'properties' => $bankAccount->toArray(),
            ]);
        });

        return back()->with('success', 'Bank account and linked Ledger Account added successfully.');
    }

    /**
     * Update a bank account with Named Error Bag ('updateBank').
     */
    public function updateBank(Request $request, BankAccount $bankAccount)
    {
        $tenant = auth()->user()->tenant;

        if ($bankAccount->tenant_id !== $tenant->id) {
            abort(403);
        }

        // Named Error Bag for Update Modal
        $validator = Validator::make($request->all(), [
            'bank_name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'currency' => ['required', 'string', 'size:3'],
            'branch_code' => ['nullable', 'string', 'max:255'],
            'swift_code' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'updateBank')->withInput();
        }

        $validated = $validator->validated();
        $oldData = $bankAccount->toArray();

        DB::transaction(function () use ($validated, $bankAccount, $request, $oldData) {
            // 1. Update Bank Account
            $bankAccount->update($validated);

            // 2. Sync Linked Chart of Accounts Entry (Name & Description only)
            if ($bankAccount->account_id) {
                $linkedAccount = Account::find($bankAccount->account_id);
                if ($linkedAccount) {
                     $linkedAccount->update([
                        'name' => $validated['bank_name'] . ' - ' . $validated['account_name'],
                        'description' => 'Linked to Bank Account: ' . $validated['account_number'],
                     ]);
                }
            }

            // 3. Activity Log
            ActivityLog::create([
                'tenant_id' => Auth::user()->tenant_id,
                'user_id' => Auth::id(),
                'action' => 'updated',
                'description' => "Updated Bank Account: {$bankAccount->bank_name} - {$bankAccount->account_number}",
                'subject_type' => BankAccount::class,
                'subject_id' => $bankAccount->id,
                'ip_address' => $request->ip(),
                'properties' => [
                    'old' => $oldData,
                    'new' => $bankAccount->fresh()->toArray()
                ],
            ]);
        });

        return back()->with('success', 'Bank account updated successfully.');
    }

    /**
     * Remove (Deactivate) a bank account.
     */
    public function destroyBank(BankAccount $bankAccount)
    {
        $tenant = auth()->user()->tenant;
        if ($bankAccount->tenant_id !== $tenant->id) {
            abort(403);
        }

        $oldData = $bankAccount->toArray();

        DB::transaction(function () use ($bankAccount, $oldData) {
            // 1. Deactivate Bank Account (Soft Delete equivalent)
            $bankAccount->update(['is_active' => false]);

            // 2. Deactivate Linked Chart of Accounts Entry
            if ($bankAccount->account_id) {
                $linkedAccount = Account::find($bankAccount->account_id);
                if ($linkedAccount) {
                    $linkedAccount->update(['is_active' => false]);
                }
            }

            // 3. Activity Log
            ActivityLog::create([
                'tenant_id' => Auth::user()->tenant_id,
                'user_id' => Auth::id(),
                'action' => 'deactivated',
                'description' => "Deactivated Bank Account and Linked COA: {$oldData['bank_name']} - {$oldData['account_number']}",
                'subject_type' => BankAccount::class,
                'subject_id' => $bankAccount->id,
                'ip_address' => request()->ip(),
                'properties' => $oldData,
            ]);
        });

        return back()->with('success', 'Bank account removed.');
    }
}
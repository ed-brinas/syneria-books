<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BankAccount;
use App\Models\Account;
use App\Models\Branch; // Added
use App\Models\Tenant; // Added
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
        
        // Load active bank accounts
        $tenant->load(['bankAccounts' => function($query) {
            $query->where('is_active', true);
        }]);

        // Load Branches (New)
        $branches = $tenant->branches()->orderBy('is_default', 'desc')->get();

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
            'Corporation' => 'Corporation / Inc / PLC',
            'NonProfit' => 'Non-Profit Organization',
        ];

        $businessTypes = ($tenant->country === 'PH') ? $businessTypesPH : $businessTypesIntl;

        return view('settings.organization.index', compact(
            'tenant', 
            'countries', 
            'currencies', 
            'businessTypes',
            'branches' // Passed to view
        ));
    }

    /**
     * Update Organization Details (Logo, Address, etc.)
     */
    public function update(Request $request)
    {
        $tenant = auth()->user()->tenant;
        
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // 2MB Max
            'company_name' => 'required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'company_reg_number' => 'nullable|string|max:50',
            'tax_identification_number' => 'nullable|string|max:50',
            'business_type' => 'nullable|string|max:100',
            'business_address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:50',
            'currency' => 'required|string|size:3',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('error', 'Please check the form for errors.');
        }

        $oldData = $tenant->toArray();

        // 2. Handle Logo Upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($tenant->logo_path && Storage::disk('s3')->exists($tenant->logo_path)) {
                Storage::disk('s3')->delete($tenant->logo_path);
            }

            // Upload new logo to S3
            $path = $request->file('logo')->store('logos/' . $tenant->id, 's3');
            $tenant->logo_path = $path;
        }

        // 3. Update Text Fields
        $tenant->fill($request->except('logo'));
        $tenant->save();

        // 4. Activity Log
        ActivityLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => Auth::id(),
            'action' => 'updated',
            'description' => "Updated Organization Profile: {$tenant->company_name}",
            'subject_type' => Tenant::class,
            'subject_id' => $tenant->id,
            'ip_address' => request()->ip(),
            'properties' => ['old' => $oldData, 'new' => $tenant->fresh()->toArray()],
        ]);

        return back()->with('success', 'Organization profile updated successfully.');
    }

    // --- Branch Management (New) ---

    public function storeBranch(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10',
            'tin' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        $tenant = Auth::user()->tenant;

        $tenant->branches()->create([
            'name' => $request->name,
            'code' => $request->code,
            'tin' => $request->tin,
            'address' => $request->address,
            'is_default' => false,
        ]);

        return redirect()->back()->with('success', 'Branch created successfully.');
    }

    public function updateBranch(Request $request, Branch $branch)
    {
        // Security check: ensure branch belongs to tenant
        if($branch->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10',
            'tin' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        $branch->update($request->only(['name', 'code', 'tin', 'address']));

        if ($request->has('set_default')) {
            $branch->markAsDefault();
        }

        return redirect()->back()->with('success', 'Branch updated successfully.');
    }

    // --- Bank Account Management (Existing) ---

    public function storeBank(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'currency' => 'required|string|size:3',
            'swift_code' => 'nullable|string|max:20',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('error', 'Bank account validation failed.');
        }

        DB::transaction(function () use ($request) {
            $tenantId = Auth::user()->tenant_id;
            
            // 1. Create Chart of Accounts Entry (Asset)
            $coa = Account::create([
                'tenant_id' => $tenantId,
                'code' => '1000-' . rand(100, 999), // Simple generator logic
                'name' => $request->bank_name . ' - ' . $request->currency,
                'type' => 'Asset',
                'subtype' => 'Cash and Cash Equivalents',
                'description' => 'Bank Account Linked: ' . $request->account_number,
                'is_active' => true,
                'is_system' => false,
            ]);

            // 2. Create Bank Account
            $bank = BankAccount::create([
                'tenant_id' => $tenantId,
                'account_id' => $coa->id, // Link to COA
                'bank_name' => $request->bank_name,
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
                'currency' => $request->currency,
                'swift_code' => $request->swift_code,
                'is_active' => true,
            ]);

            // 3. Log Activity
            ActivityLog::create([
                'tenant_id' => $tenantId,
                'user_id' => Auth::id(),
                'action' => 'created',
                'description' => "Added Bank Account: {$bank->bank_name} ({$bank->currency})",
                'subject_type' => BankAccount::class,
                'subject_id' => $bank->id,
                'ip_address' => request()->ip(),
            ]);
        });

        return back()->with('success', 'Bank account added successfully.');
    }

    public function updateBank(Request $request, BankAccount $bankAccount)
    {
        $tenant = auth()->user()->tenant;
        if ($bankAccount->tenant_id !== $tenant->id) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'currency' => 'required|string|size:3',
            'swift_code' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $bankAccount->update($request->all());

        // Update linked COA name if necessary
        if ($bankAccount->account_id) {
            $account = Account::find($bankAccount->account_id);
            if ($account) {
                $account->update(['name' => $request->bank_name . ' - ' . $request->currency]);
            }
        }

        return back()->with('success', 'Bank account updated successfully.');
    }

    public function destroyBank(BankAccount $bankAccount)
    {
        $tenant = auth()->user()->tenant;
        if ($bankAccount->tenant_id !== $tenant->id) {
            abort(403);
        }

        $oldData = $bankAccount->toArray();

        DB::transaction(function () use ($bankAccount, $oldData) {
            $bankAccount->update(['is_active' => false]);

            if ($bankAccount->account_id) {
                $linkedAccount = Account::find($bankAccount->account_id);
                if ($linkedAccount) {
                    $linkedAccount->update(['is_active' => false]);
                }
            }

            ActivityLog::create([
                'tenant_id' => Auth::user()->tenant_id,
                'user_id' => Auth::id(),
                'action' => 'deactivated',
                'description' => "Deactivated Bank Account: {$oldData['bank_name']}",
                'subject_type' => BankAccount::class,
                'subject_id' => $bankAccount->id,
                'ip_address' => request()->ip(),
                'properties' => $oldData,
            ]);
        });

        return back()->with('success', 'Bank account removed.');
    }
}
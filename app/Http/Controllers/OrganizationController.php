<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BankAccount;
use App\Models\Account;
use App\Models\Branch; 
use App\Models\Tenant; 
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

        // Load Branches (Ordered by Code for BIR standard listing)
        $branches = $tenant->branches()
            ->orderBy('is_default', 'desc')
            ->orderBy('code', 'asc')
            ->get();

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
            'branches'
        ));
    }

    /**
     * Update Organization Details (Logo, Address, etc.)
     */
    public function update(Request $request)
    {
        $tenant = auth()->user()->tenant;
        
        $validator = Validator::make($request->all(), [
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', 
            'company_name' => 'required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'company_reg_number' => 'nullable|string|max:50',
            'tax_identification_number' => 'nullable|string|max:50',
            'business_type' => 'nullable|string|max:100',
            'business_address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('error', 'Please check the form for errors.');
        }

        $oldData = $tenant->toArray();

        if ($request->hasFile('logo')) {
            if ($tenant->logo_path && Storage::disk('s3')->exists($tenant->logo_path)) {
                Storage::disk('s3')->delete($tenant->logo_path);
            }
            $path = $request->file('logo')->storePublicly('logos/' . $tenant->id, 's3');
            $tenant->logo_path = $path;
        }

        $tenant->fill($request->except('logo'));
        $tenant->save();

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

    // --- Branch Management ---

    public function storeBranch(Request $request)
    {
        $tenant = Auth::user()->tenant;
        $isPH = $tenant->country === 'PH';

        // Validation with Unique Constraint & Country-Specific Format
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => [
                'required', 
                'string', 
                // PH: Numeric, 3-5 digits. International: Max 10 chars.
                $isPH ? 'regex:/^\d{3,5}$/' : 'max:10',
                Rule::unique('branches')->where(fn ($query) => $query->where('tenant_id', $tenant->id))
            ],
            'tin' => 'nullable|string|max:20',
            'rdo_code' => 'nullable|string|max:10',
            'address' => 'required|string|max:500',
            'city' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ], [
            'code.regex' => 'For Philippines, Branch Code must be 3 to 5 digits (e.g., 001).',
            'code.unique' => 'This Branch Code is already in use.',
        ]);

        if ($validator->fails()) {
            // 'addBranch' bag ensures the "Add Branch" modal re-opens on error
            return back()->withErrors($validator, 'addBranch')->withInput()->with('error', 'Branch validation failed.');
        }

        $branch = $tenant->branches()->create([
            'name' => $request->name,
            'code' => $request->code,
            'tin' => $request->tin,
            'rdo_code' => $request->rdo_code,
            'address' => $request->address,
            'city' => $request->city,
            'zip_code' => $request->zip_code,
            'phone' => $request->phone,
            'email' => $request->email,
            'is_default' => false,
            'is_active' => true,
        ]);

        ActivityLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => Auth::id(),
            'action' => 'created',
            'description' => "Created Branch: {$branch->name} ({$branch->code})",
            'subject_type' => Branch::class,
            'subject_id' => $branch->id,
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', 'Branch created successfully.');
    }

    public function updateBranch(Request $request, Branch $branch)
    {
        if($branch->tenant_id !== Auth::user()->tenant_id) abort(403);
        $tenant = Auth::user()->tenant;
        $isPH = $tenant->country === 'PH';

        // Validation with Unique Constraint & Country-Specific Format
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => [
                'required', 
                'string', 
                $isPH ? 'regex:/^\d{3,5}$/' : 'max:10',
                Rule::unique('branches')
                    ->ignore($branch->id) 
                    ->where(fn ($query) => $query->where('tenant_id', $branch->tenant_id))
            ],
            'tin' => 'nullable|string|max:20',
            'rdo_code' => 'nullable|string|max:10',
            'address' => 'required|string|max:500',
            'city' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean'
        ], [
            'code.regex' => 'For Philippines, Branch Code must be 3 to 5 digits (e.g., 001).',
            'code.unique' => 'This Branch Code is already in use.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'updateBranch')->withInput()->with('error', 'Branch update failed.');
        }

        $oldData = $branch->toArray();

        $branch->update($request->only([
            'name', 'code', 'tin', 'rdo_code', 
            'address', 'city', 'zip_code', 
            'phone', 'email', 'is_active'
        ]));

        if ($request->has('set_default') && $request->set_default) {
            $branch->markAsDefault();
        }

        ActivityLog::create([
            'tenant_id' => $branch->tenant_id,
            'user_id' => Auth::id(),
            'action' => 'updated',
            'description' => "Updated Branch: {$branch->name} ({$branch->code})",
            'subject_type' => Branch::class,
            'subject_id' => $branch->id,
            'ip_address' => request()->ip(),
            'properties' => ['old' => $oldData, 'new' => $branch->fresh()->toArray()],
        ]);

        return back()->with('success', 'Branch updated successfully.');
    }

    public function destroyBranch(Branch $branch)
    {
        if($branch->tenant_id !== Auth::user()->tenant_id) abort(403);
        
        if($branch->is_default) {
            return back()->with('error', 'The Main Branch cannot be deleted.');
        }

        $branch->delete();

        ActivityLog::create([
            'tenant_id' => $branch->tenant_id,
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'description' => "Deactivated Branch: {$branch->name} ({$branch->code})",
            'subject_type' => Branch::class,
            'subject_id' => $branch->id,
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', 'Branch deactivated.');
    }

    // --- Bank Account Management ---

    public function storeBank(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'currency' => 'required|string|size:3',
            'swift_code' => 'nullable|string|max:20',
            'branch_code' => 'nullable|string|max:20', // Added for BRSTN
            'address' => 'nullable|string|max:500',    // Added for Bank Address
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'addBank')->withInput()->with('error', 'Bank account validation failed.');
        }

        DB::transaction(function () use ($request) {
            $tenantId = Auth::user()->tenant_id;
            
            $coaCode = $request->coa_code ?? ('1000-' . rand(100, 999));
            $coaName = $request->coa_name ?? ($request->bank_name . ' - ' . $request->currency);

            $coa = Account::create([
                'tenant_id' => $tenantId,
                'code' => $coaCode,
                'name' => $coaName,
                'type' => $request->coa_type ?? 'Asset',
                'subtype' => $request->coa_subtype ?? 'Cash and Cash Equivalents',
                'description' => 'Bank Account Linked: ' . $request->account_number,
                'is_active' => true,
                'is_system' => false,
            ]);

            $bank = BankAccount::create([
                'tenant_id' => $tenantId,
                'account_id' => $coa->id,
                'bank_name' => $request->bank_name,
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
                'currency' => $request->currency,
                'swift_code' => $request->swift_code,
                'branch_code' => $request->branch_code, // Store BRSTN
                'address' => $request->address,         // Store Address
                'is_active' => true,
            ]);

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
            'branch_code' => 'nullable|string|max:20', // Added
            'address' => 'nullable|string|max:500',    // Added
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'updateBank')->withInput();
        }

        $oldData = $bankAccount->toArray();

        $bankAccount->update($request->all());

        if ($bankAccount->account_id) {
            $account = Account::find($bankAccount->account_id);
            if ($account) {
                $account->update(['name' => $request->bank_name . ' - ' . $request->currency]);
            }
        }

        ActivityLog::create([
            'tenant_id' => $bankAccount->tenant_id,
            'user_id' => Auth::id(),
            'action' => 'updated',
            'description' => "Updated Bank Account: {$bankAccount->bank_name}",
            'subject_type' => BankAccount::class,
            'subject_id' => $bankAccount->id,
            'ip_address' => request()->ip(),
            'properties' => ['old' => $oldData, 'new' => $bankAccount->fresh()->toArray()],
        ]);

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
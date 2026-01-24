<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Currencies; 

class OrganizationController extends Controller
{
    /**
     * Display the organization settings page.
     */
    public function index()
    {
        $countries = Countries::getNames();
        $currencies = Currencies::getNames();
        // Assuming the current tenant is retrieved via middleware or auth user
        // Adjust this retrieval method based on your multi-tenancy implementation
        $tenant = auth()->user()->tenant; 

        return view('settings.organization.index', compact('tenant','countries','currencies'));
    }

    /**
     * Update organization details and contact info.
     */
    public function update(Request $request)
    {
        $tenant = auth()->user()->tenant;

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

        return back()->with('success', 'Organization details updated successfully.');
    }

    /**
     * Upload and update the organization logo.
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048', 'dimensions:max_width=1000,max_height=1000'],
        ]);

        $tenant = auth()->user()->tenant;

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($tenant->logo_path && Storage::exists($tenant->logo_path)) {
                Storage::delete($tenant->logo_path);
            }

            // Store new logo publicly
            $path = $request->file('logo')->store('public/logos/' . $tenant->id);
            
            $tenant->update(['logo_path' => $path]);
        }

        return back()->with('success', 'Logo uploaded successfully.');
    }

    /**
     * Store a new bank account.
     */
    public function storeBank(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'currency' => ['required', 'string', 'size:3'],
        ]);

        $tenant = auth()->user()->tenant;
        
        $tenant->bankAccounts()->create($validated);

        return back()->with('success', 'Bank account added successfully.');
    }

    /**
     * Remove a bank account.
     */
    public function destroyBank(BankAccount $bankAccount)
    {
        $tenant = auth()->user()->tenant;

        // Ensure the bank account belongs to the current tenant
        if ($bankAccount->tenant_id !== $tenant->id) {
            abort(403);
        }

        $bankAccount->delete();

        return back()->with('success', 'Bank account removed.');
    }
}
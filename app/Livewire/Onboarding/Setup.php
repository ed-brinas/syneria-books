<?php

namespace App\Livewire\Onboarding;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Symfony\Component\Intl\Countries;
use Database\Seeders\ChartOfAccountsSeeder;
use App\Models\ActivityLog;

class Setup extends Component
{
    public $first_name = '';
    public $last_name = '';
    public $phone = '';
    public $company_name = '';
    public $country = 'PH';
    public $business_type = '';
    public $position = ''; 
    public $trade_name = '';
    public $company_reg_number = '';
    public $tax_identification_number = '';
    public $business_address = '';
    public $city = '';
    public $postal_code = '';
    public $import_coa = true; 

    protected $rules = [
        'first_name' => 'required|string|max:50',
        'last_name' => 'required|string|max:50',
        'phone' => 'required|string|max:20',
        'position' => 'required|string|max:100',
        'company_name' => 'required|string|max:255',
        'country' => 'required|string|max:50',
        'business_type' => 'required|string|max:50',
        'trade_name' => 'nullable|string|max:255',
        'company_reg_number' => 'nullable|string|max:50',
        'tax_identification_number' => 'nullable|string|max:50',
        'business_address' => 'required|string|max:500',
        'city' => 'required|string|max:100',
        'postal_code' => 'required|string|max:20',
        'import_coa' => 'boolean',
    ];

    public function mount()
    {
        // If user already has a tenant, kick them to dashboard
        if (Auth::user()->tenant_id) {
            $user = Auth::user();
            $user->update(['last_login_at' => now()]);        
            return redirect()->route('dashboard');
        }
    }

    // Dynamic Business Types based on selected Country
    public function getBusinessTypesProperty()
    {
        if ($this->country === 'PH') {
            return [
                'Sole Proprietorship' => 'Sole Proprietorship',
                'Partnership' => 'Partnership',
                'Corporation' => 'Corporation',
                'OPC' => 'One Person Corporation (OPC)',
                'Cooperative' => 'Cooperative',
                'Foundation' => 'Foundation / Non-Stock Non-Profit',
            ];
        }

        // International Defaults (US, UK, AU, etc.)
        return [
            'Sole Proprietorship' => 'Sole Proprietorship / Sole Trader',
            'Partnership' => 'Partnership / LLP',
            'LLC' => 'LLC / Ltd',
            'Corporation' => 'Corporation / Pty Ltd',
            'Non-Profit' => 'Non-Profit / Charity',
        ];
    }
    
    // Position Suggestions
    public function getPositionsProperty()
    {
        return [
            'Owner / Proprietor',
            'President / CEO',
            'Chief Financial Officer (CFO)',
            'Finance Manager',
            'Accountant / Bookkeeper',
            'Administrative Officer',
            'Sales Manager',
            'IT / System Admin',
            'Other'
        ];
    }
    
    // Get ALL countries via symfony/intl
    public function getCountriesProperty()
    {
        return Countries::getNames();
    }

    public function completeSetup()
    {
        $this->validate();

        DB::transaction(function () {
            // 1. Create Tenant (Legal Entity)
            $tenant = Tenant::create([
                'id' => (string) Str::orderedUuid(),
                'company_name' => $this->company_name,
                'trade_name' => $this->trade_name,
                'company_reg_number' => $this->company_reg_number,
                'tax_identification_number' => $this->tax_identification_number,
                'business_address' => $this->business_address,
                'city' => $this->city, 
                'postal_code' => $this->postal_code, 
                'business_type' => $this->business_type,
                'country' => $this->country,
                'subscription_plan' => 'free',
                'subscription_expires_at' => now()->addDays(30),
            ]);

            // 2. Create Default Branch (Head Office)
            // Aligned with strict BIR compliance structure
            $tenant->branches()->create([
                'name' => 'Head Office', // Default to standard naming
                'code' => '000', // Standard BIR Code for Head Office
                'tin' => $this->tax_identification_number, // Inherit TIN base
                'rdo_code' => null, // To be updated in Settings > Organization later
                'address' => $this->business_address,
                'city' => $this->city,
                'zip_code' => $this->postal_code,
                'province' => null,
                'phone' => $this->phone, // Use user contact as initial branch contact
                'email' => Auth::user()->email,
                'is_default' => true,
                'is_active' => true,
            ]);
                        
            // 3. Update User
            $user = Auth::user();
            $user->update([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'name' => $this->first_name . ' ' . $this->last_name,
                'phone' => $this->phone,
                'position' => $this->position,
                'tenant_id' => $tenant->id,
                'role' => 'SuperAdministrator',
                'last_login_at' => now(),
                'status' => 'active',
            ]);

            // 4. Optional Seeding of Chart of Accounts
            if ($this->import_coa && Auth::user()->tenant_id) {
                $seeder = new ChartOfAccountsSeeder();
                $user->load('tenant'); 
                $seeder->run(); 
            }

            // 5. Activity Log (Onboarding Completion)
            ActivityLog::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => 'created',
                'description' => "Completed Onboarding - Created Organization: {$tenant->company_name}",
                'subject_type' => Tenant::class,
                'subject_id' => $tenant->id,
                'ip_address' => request()->ip(),
                'properties' => $tenant->toArray(), 
            ]);           
        });

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.onboarding.setup')->layout('layouts.app', ['title' => 'Setup Organization']);
    }
}
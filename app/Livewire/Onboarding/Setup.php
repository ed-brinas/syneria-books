<?php

namespace App\Livewire\Onboarding;

use App\Models\Tenant;
use App\Models\ActivityLog;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Symfony\Component\Intl\Countries;

class Setup extends Component
{
    public $first_name = '';
    public $last_name = '';
    public $phone = '';
    public $position = ''; 
    
    public $company_name = '';
    public $trade_name = '';
    public $company_reg_number = '';
    public $tax_identification_number = '';
    public $business_type = '';
    public $business_address = '';
    public $city = '';
    public $postal_code = '';
    public $country = 'PH';
    
    public $import_coa = true; 

    protected $rules = [
        'first_name' => 'required|string|max:50',
        'last_name' => 'required|string|max:50',
        'phone' => 'required|string|max:20',
        'position' => 'required|string|max:100',
        'company_name' => 'required|string|max:255',
        'trade_name' => 'nullable|string|max:255',
        'company_reg_number' => 'required|string|max:50',
        'tax_identification_number' => 'required|string|max:50',
        'business_type' => 'required|string|max:50',
        'business_address' => 'required|string|max:500',
        'city' => 'required|string|max:100',
        'postal_code' => 'required|string|max:20',
        'country' => 'required|string|max:50',
        'import_coa' => 'boolean',
    ];

    public function submit()
    {
        $this->validate();

        DB::transaction(function () {
            // 1. Create Tenant (Organization)
            $tenant = Tenant::create([
                'company_name' => $this->company_name,
                'trade_name' => $this->trade_name,
                'company_reg_number' => $this->company_reg_number,
                'tax_identification_number' => $this->tax_identification_number,
                'business_type' => $this->business_type,
                'business_address' => $this->business_address,
                'city' => $this->city,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
                'subscription_plan' => 'free', // Default plan
                'subscription_expires_at' => null, // No expiration for free plan
            ]);

            // 2. Create Default Branch (Multi-Branch Requirement)
            // We map the main organization details to this initial branch
            $tenant->branches()->create([
                'name' => $this->company_name,
                'code' => 'MAIN',
                'is_default' => true,
                'address' => $this->business_address,
                'city' => $this->city,
                'zip_code' => $this->postal_code,
                'tin' => $this->tax_identification_number,
            ]);

            // 3. Update User Profile & Link to Tenant
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $user->update([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'name' => $this->first_name . ' ' . $this->last_name, // Concatenate for standard name field
                'phone' => $this->phone,
                'position' => $this->position,
                'tenant_id' => $tenant->id,
                'role' => 'SuperAdministrator',
                'last_login_at' => now(),
                'status' => 'active',
                'is_owner' => true,
            ]);

            // 4. Seeding of Chart of Accounts
            if ($this->import_coa) {
                // Ensure we temporarily set the tenant context for the seeder if it relies on Auth::user()->tenant_id
                // Since we just updated the user, Auth::user()->tenant_id should be available, 
                // but we refresh the user instance just in case.
                $user->refresh();
                $seeder = new ChartOfAccountsSeeder();
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
        return view('livewire.onboarding.setup', [
            'countries' => Countries::getNames(),
        ])->layout('layouts.app', ['title' => 'Setup Organization']);
    }
}
<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Determine Context
        // Try to get the tenant from the authenticated user (if running via web/setup wizard)
        // OR default to the first tenant (if running via CLI 'php artisan db:seed')
        $tenant = Auth::user()?->tenant ?? Tenant::first();

        if (!$tenant) {
            // Use null-safe operator ?-> to prevent crash if not running in CLI
            $this->command?->warn('No tenant found. Seeder skipped.');
            return;
        }

        $country = $tenant->country ?? 'PH';
        
        $accounts = match($country) {
            'PH' => $this->getPhilippineAccounts(),
            default => $this->getInternationalAccounts(),
        };

        // 2. Execution Logic
        foreach ($accounts as $acc) {
            // Check if account code already exists for this tenant to prevent duplicates
            $exists = Account::where('tenant_id', $tenant->id)
                ->where('code', $acc['code'])
                ->exists();

            if (!$exists) {
                Account::create([
                    'tenant_id' => $tenant->id, // Explicitly set tenant_id
                    'code' => $acc['code'],
                    'name' => $acc['name'],
                    'type' => strtolower($acc['type']), // Convert to lowercase to match Controller validation
                    'subtype' => $acc['subtype'], // Keep casing or strtolower based on preference, keeping as is for display
                    'is_system' => true, // Mark these as system defaults so they can't be easily deleted
                    'is_active' => true,
                    'description' => 'Standard system account'
                ]);
            }
        }
        
        // Use null-safe operator ?-> here as well
        $this->command?->info("Seeded " . count($accounts) . " accounts for Tenant: {$tenant->company_name}");
    }

    private function getPhilippineAccounts(): array
    {
        // PPSAS / RCA / UACS Compliant Structure (8-digit codes)
        // types converted to match strict Enum in migration/controller
        return [
            // --- ASSETS (1) ---
            ['code' => '10101010', 'name' => 'Cash in Vault', 'type' => 'asset', 'subtype' => 'Cash'],
            ['code' => '10101020', 'name' => 'Petty Cash', 'type' => 'asset', 'subtype' => 'Cash'],
            ['code' => '10102010', 'name' => 'Cash in Bank - Local Currency, Current Account', 'type' => 'asset', 'subtype' => 'Cash'],
            ['code' => '10301010', 'name' => 'Accounts Receivable', 'type' => 'asset', 'subtype' => 'Receivable'],
            ['code' => '10404010', 'name' => 'Merchandise Inventory', 'type' => 'asset', 'subtype' => 'Inventory'],
            ['code' => '10604010', 'name' => 'Buildings', 'type' => 'asset', 'subtype' => 'Fixed Asset'],
            ['code' => '10605020', 'name' => 'Office Equipment', 'type' => 'asset', 'subtype' => 'Fixed Asset'],

            // --- LIABILITIES (2) ---
            ['code' => '20101010', 'name' => 'Accounts Payable', 'type' => 'liability', 'subtype' => 'Payable'],
            ['code' => '20201010', 'name' => 'Due to BIR', 'type' => 'liability', 'subtype' => 'Tax'], 
            ['code' => '20201020', 'name' => 'Due to GSIS', 'type' => 'liability', 'subtype' => 'Statutory'],
            ['code' => '20201030', 'name' => 'Due to Pag-IBIG', 'type' => 'liability', 'subtype' => 'Statutory'],
            ['code' => '20201040', 'name' => 'Due to PhilHealth', 'type' => 'liability', 'subtype' => 'Statutory'],

            // --- EQUITY (3) ---
            ['code' => '30101010', 'name' => 'Government Equity', 'type' => 'equity', 'subtype' => 'Equity'],

            // --- INCOME (4) ---
            ['code' => '40201010', 'name' => 'Service Income', 'type' => 'revenue', 'subtype' => 'Income'],
            ['code' => '40202160', 'name' => 'Sales Revenue', 'type' => 'revenue', 'subtype' => 'Income'],

            // --- EXPENSES (5) ---
            ['code' => '50101010', 'name' => 'Salaries and Wages - Regular', 'type' => 'expense', 'subtype' => 'Operating'],
            ['code' => '50203010', 'name' => 'Office Supplies Expense', 'type' => 'expense', 'subtype' => 'Operating'],
            ['code' => '50209010', 'name' => 'Rent/Lease Expenses', 'type' => 'expense', 'subtype' => 'Operating'],
        ];
    }

    private function getInternationalAccounts(): array
    {
        return [
            // --- ASSETS (1000-1999) ---
            ['code' => '1000', 'name' => 'Cash on Hand', 'type' => 'asset', 'subtype' => 'Cash'],
            ['code' => '1010', 'name' => 'Business Checking Account', 'type' => 'asset', 'subtype' => 'Cash'],
            ['code' => '1020', 'name' => 'Savings Account', 'type' => 'asset', 'subtype' => 'Cash'],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'subtype' => 'Receivable'],
            ['code' => '1205', 'name' => 'Allowance for Doubtful Accounts', 'type' => 'asset', 'subtype' => 'Contra Asset'],
            ['code' => '1500', 'name' => 'Inventory', 'type' => 'asset', 'subtype' => 'Inventory'],
            ['code' => '1600', 'name' => 'Office Equipment', 'type' => 'asset', 'subtype' => 'Fixed Asset'],
            ['code' => '1700', 'name' => 'Accumulated Depreciation', 'type' => 'asset', 'subtype' => 'Contra Asset'],

            // --- LIABILITIES (2000-2999) ---
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'subtype' => 'Payable'],
            ['code' => '2010', 'name' => 'Credit Card Payable', 'type' => 'liability', 'subtype' => 'Credit Card'],
            ['code' => '2300', 'name' => 'Sales Tax Payable', 'type' => 'liability', 'subtype' => 'Tax'],
            ['code' => '2400', 'name' => 'Payroll Tax Payable', 'type' => 'liability', 'subtype' => 'Tax'],

            // --- EQUITY (3000-3999) ---
            ['code' => '3000', 'name' => 'Owner\'s Capital', 'type' => 'equity', 'subtype' => 'Equity'],
            ['code' => '3010', 'name' => 'Owner\'s Draw', 'type' => 'equity', 'subtype' => 'Equity'],
            ['code' => '3200', 'name' => 'Retained Earnings', 'type' => 'equity', 'subtype' => 'Equity'],

            // --- REVENUE (4000-4999) ---
            ['code' => '4000', 'name' => 'Sales', 'type' => 'revenue', 'subtype' => 'Income'],
            ['code' => '4100', 'name' => 'Service Revenue', 'type' => 'revenue', 'subtype' => 'Income'],
            ['code' => '4200', 'name' => 'Interest Income', 'type' => 'revenue', 'subtype' => 'Other Income'],

            // --- EXPENSES (5000-9999) ---
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'subtype' => 'COGS'],
            ['code' => '6000', 'name' => 'Advertising and Promotion', 'type' => 'expense', 'subtype' => 'Operating'],
            ['code' => '6010', 'name' => 'Bank Service Charges', 'type' => 'expense', 'subtype' => 'Operating'],
            ['code' => '6100', 'name' => 'Insurance Expense', 'type' => 'expense', 'subtype' => 'Operating'],
            ['code' => '6200', 'name' => 'Rent Expense', 'type' => 'expense', 'subtype' => 'Operating'],
            ['code' => '6300', 'name' => 'Salaries and Wages', 'type' => 'expense', 'subtype' => 'Operating'],
            ['code' => '6400', 'name' => 'Telephone and Internet', 'type' => 'expense', 'subtype' => 'Operating'],
            ['code' => '6500', 'name' => 'Travel and Meals', 'type' => 'expense', 'subtype' => 'Operating'],
        ];
    }
}
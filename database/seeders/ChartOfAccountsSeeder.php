<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Determine Context (For production, this runs per tenant setup)
        // In the MVP seeder, we will assume we are seeding for the currently authenticated tenant
        // or a specific tenant passed to the seeder.
        
        $tenant = Auth::user()?->tenant ?? Tenant::first();
        if (!$tenant) return;

        $country = $tenant->country ?? 'PH';
        
        $accounts = match($country) {
            'PH' => $this->getPhilippineAccounts(),
            default => $this->getInternationalAccounts(),
        };

        // Seed Logic (Pseudo-code for MVP structure)
        // foreach ($accounts as $acc) { Account::create($acc); }
    }

    private function getPhilippineAccounts(): array
    {
        // PPSAS / RCA / UACS Compliant Structure (8-digit codes)
        return [
            // --- ASSETS (1) ---
            ['code' => '10101010', 'name' => 'Cash in Vault', 'type' => 'Asset', 'subtype' => 'Cash'],
            ['code' => '10101020', 'name' => 'Petty Cash', 'type' => 'Asset', 'subtype' => 'Cash'],
            ['code' => '10102010', 'name' => 'Cash in Bank - Local Currency, Current Account', 'type' => 'Asset', 'subtype' => 'Cash'],
            ['code' => '10301010', 'name' => 'Accounts Receivable', 'type' => 'Asset', 'subtype' => 'Receivable'],
            ['code' => '10404010', 'name' => 'Merchandise Inventory', 'type' => 'Asset', 'subtype' => 'Inventory'],
            ['code' => '10604010', 'name' => 'Buildings', 'type' => 'Asset', 'subtype' => 'Fixed Asset'],
            ['code' => '10605020', 'name' => 'Office Equipment', 'type' => 'Asset', 'subtype' => 'Fixed Asset'],

            // --- LIABILITIES (2) ---
            ['code' => '20101010', 'name' => 'Accounts Payable', 'type' => 'Liability', 'subtype' => 'Payable'],
            ['code' => '20201010', 'name' => 'Due to BIR', 'type' => 'Liability', 'subtype' => 'Tax'], 
            ['code' => '20201020', 'name' => 'Due to GSIS', 'type' => 'Liability', 'subtype' => 'Statutory'],
            ['code' => '20201030', 'name' => 'Due to Pag-IBIG', 'type' => 'Liability', 'subtype' => 'Statutory'],
            ['code' => '20201040', 'name' => 'Due to PhilHealth', 'type' => 'Liability', 'subtype' => 'Statutory'],

            // --- EQUITY (3) ---
            ['code' => '30101010', 'name' => 'Government Equity', 'type' => 'Equity', 'subtype' => 'Equity'],

            // --- INCOME (4) ---
            ['code' => '40201010', 'name' => 'Service Income', 'type' => 'Revenue', 'subtype' => 'Income'],
            ['code' => '40202160', 'name' => 'Sales Revenue', 'type' => 'Revenue', 'subtype' => 'Income'],

            // --- EXPENSES (5) ---
            ['code' => '50101010', 'name' => 'Salaries and Wages - Regular', 'type' => 'Expense', 'subtype' => 'Operating'],
            ['code' => '50203010', 'name' => 'Office Supplies Expense', 'type' => 'Expense', 'subtype' => 'Operating'],
            ['code' => '50209010', 'name' => 'Rent/Lease Expenses', 'type' => 'Expense', 'subtype' => 'Operating'],
        ];
    }

    private function getInternationalAccounts(): array
    {
        // Standard GAAP / IFRS Friendly Structure (4-digit codes)
        // Suitable for US, Canada, UK, Australia, Singapore
        return [
            // --- ASSETS (1000-1999) ---
            ['code' => '1000', 'name' => 'Cash on Hand', 'type' => 'Asset', 'subtype' => 'Cash'],
            ['code' => '1010', 'name' => 'Business Checking Account', 'type' => 'Asset', 'subtype' => 'Cash'],
            ['code' => '1020', 'name' => 'Savings Account', 'type' => 'Asset', 'subtype' => 'Cash'],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'Asset', 'subtype' => 'Receivable'],
            ['code' => '1205', 'name' => 'Allowance for Doubtful Accounts', 'type' => 'Asset', 'subtype' => 'Contra Asset'],
            ['code' => '1500', 'name' => 'Inventory', 'type' => 'Asset', 'subtype' => 'Inventory'],
            ['code' => '1600', 'name' => 'Office Equipment', 'type' => 'Asset', 'subtype' => 'Fixed Asset'],
            ['code' => '1700', 'name' => 'Accumulated Depreciation', 'type' => 'Asset', 'subtype' => 'Contra Asset'],

            // --- LIABILITIES (2000-2999) ---
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'Liability', 'subtype' => 'Payable'],
            ['code' => '2010', 'name' => 'Credit Card Payable', 'type' => 'Liability', 'subtype' => 'Credit Card'],
            ['code' => '2300', 'name' => 'Sales Tax Payable', 'type' => 'Liability', 'subtype' => 'Tax'], // GST/VAT
            ['code' => '2400', 'name' => 'Payroll Tax Payable', 'type' => 'Liability', 'subtype' => 'Tax'],

            // --- EQUITY (3000-3999) ---
            ['code' => '3000', 'name' => 'Owner\'s Capital', 'type' => 'Equity', 'subtype' => 'Equity'],
            ['code' => '3010', 'name' => 'Owner\'s Draw', 'type' => 'Equity', 'subtype' => 'Equity'],
            ['code' => '3200', 'name' => 'Retained Earnings', 'type' => 'Equity', 'subtype' => 'Equity'],

            // --- REVENUE (4000-4999) ---
            ['code' => '4000', 'name' => 'Sales', 'type' => 'Revenue', 'subtype' => 'Income'],
            ['code' => '4100', 'name' => 'Service Revenue', 'type' => 'Revenue', 'subtype' => 'Income'],
            ['code' => '4200', 'name' => 'Interest Income', 'type' => 'Revenue', 'subtype' => 'Other Income'],

            // --- EXPENSES (5000-9999) ---
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'Expense', 'subtype' => 'COGS'],
            ['code' => '6000', 'name' => 'Advertising and Promotion', 'type' => 'Expense', 'subtype' => 'Operating'],
            ['code' => '6010', 'name' => 'Bank Service Charges', 'type' => 'Expense', 'subtype' => 'Operating'],
            ['code' => '6100', 'name' => 'Insurance Expense', 'type' => 'Expense', 'subtype' => 'Operating'],
            ['code' => '6200', 'name' => 'Rent Expense', 'type' => 'Expense', 'subtype' => 'Operating'],
            ['code' => '6300', 'name' => 'Salaries and Wages', 'type' => 'Expense', 'subtype' => 'Operating'],
            ['code' => '6400', 'name' => 'Telephone and Internet', 'type' => 'Expense', 'subtype' => 'Operating'],
            ['code' => '6500', 'name' => 'Travel and Meals', 'type' => 'Expense', 'subtype' => 'Operating'],
        ];
    }
}
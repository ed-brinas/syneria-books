<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Guard: If user manually navigates here without a tenant, redirect to login
        if (!$user->tenant_id) {
            // We must explicitly logout to prevent the 'RedirectIfAuthenticated' 
            // middleware from sending them right back to the dashboard.
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            
            return redirect()->route('login');
        }

        $tenant = $user->tenant;

        // MVP Mock Data (Moved from Livewire Component)
        // In Phase 2, these will be replaced by: $tenant->journalEntries()->...
        $moneyIn = 150000.00;
        $moneyOut = 85400.50;
        $cashBalance = 450000.00;
        
        $watchlist = [
            ['account' => '1010 - Cash in Bank BDO', 'balance' => 320000.00, 'trend' => 'up'],
            ['account' => '1100 - Accounts Receivable', 'balance' => 45000.00, 'trend' => 'down'],
            ['account' => '2000 - Accounts Payable', 'balance' => 12500.00, 'trend' => 'up'],
            ['account' => '4000 - Service Revenue', 'balance' => 89000.00, 'trend' => 'up'],
        ];

        // Return a standard Blade view (not a Livewire component)
        return view('dashboard', compact(
            'user', 
            'tenant', 
            'moneyIn', 
            'moneyOut', 
            'cashBalance', 
            'watchlist'
        ));
    }
}
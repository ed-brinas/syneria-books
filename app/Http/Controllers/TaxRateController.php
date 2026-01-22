<?php

namespace App\Http\Controllers;

use App\Models\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaxRateController extends Controller
{
    public function index(Request $request)
    {
        $query = TaxRate::where('tenant_id', Auth::user()->tenant_id)
            ->orderBy('is_active', 'desc') // Active first
            ->orderBy('name', 'asc');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $taxRates = $query->paginate(15);
        return view('settings.tax_rates.index', compact('taxRates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'rate' => 'required|numeric|min:0|max:100', // User enters 10 for 10%
            'type' => 'required|in:sales,purchase,both',
        ]);

        TaxRate::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'rate' => $request->rate / 100, // Convert 10 -> 0.10 for DB
            'type' => $request->type,
            'is_active' => true,
        ]);

        return back()->with('success', 'Tax Rate created successfully.');
    }

    public function update(Request $request, TaxRate $taxRate)
    {
        if ($taxRate->tenant_id !== Auth::user()->tenant_id) abort(403);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|in:sales,purchase,both',
        ]);

        $taxRate->update([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'rate' => $request->rate / 100,
            'type' => $request->type,
        ]);

        return back()->with('success', 'Tax Rate updated successfully.');
    }

    public function toggleStatus(TaxRate $taxRate)
    {
        if ($taxRate->tenant_id !== Auth::user()->tenant_id) abort(403);

        $taxRate->update([
            'is_active' => !$taxRate->is_active
        ]);

        $status = $taxRate->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Tax Rate {$status}.");
    }

    public function destroy(TaxRate $taxRate)
    {
        if ($taxRate->tenant_id !== Auth::user()->tenant_id) abort(403);
        
        // Optional: Check if used in journal entries before deleting
        // if($taxRate->journalLines()->exists()) { return back()->withErrors(...) }

        $taxRate->delete();
        return back()->with('success', 'Tax Rate deleted.');
    }
}
<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\Login;
use App\Livewire\Onboarding\Setup;

// Public Route (Entry Point)
Route::get('/', Login::class)->name('login')->middleware('guest');
Route::get('/login', function() { return redirect('/'); });

// Authenticated Routes
Route::middleware(['auth'])->group(function () {
    
    // Onboarding (For users without a Tenant)
    Route::get('/onboarding', Setup::class)->name('onboarding');
    
    // Main App
    Route::get('/dashboard', function () {
        // Guard: If user hasn't finished setup, send back to onboarding
        if (!auth()->user()->tenant_id) {
            return redirect()->route('onboarding');
        }
        return view('dashboard'); 
    })->name('dashboard');
    
    Route::post('/logout', function () {
        auth()->logout();
        session()->invalidate();
        return redirect('/');
    })->name('logout');
});

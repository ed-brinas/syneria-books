<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\Login;
use App\Livewire\Onboarding\Setup;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\JournalEntryController;

// Public Route (Entry Point)
Route::get('/', Login::class)->name('login')->middleware('guest');

// Redirect legacy /login to root
Route::get('/login', function() { 
    return redirect()->route('login'); 
});

// Authenticated Routes
Route::middleware(['auth'])->group(function () {
    
    // Onboarding (For users without a Tenant)
    Route::get('/onboarding', Setup::class)->name('onboarding');
    
    // Main App Dashboard
    // Uses the Controller logic we created, but at your preferred path
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Phase 2: Core GL Routes
    Route::resource('accounts', AccountController::class);
    Route::resource('journals', JournalEntryController::class);
    
    // Logout Action
    Route::post('/logout', function () {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});
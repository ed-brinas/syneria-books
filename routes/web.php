<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\Login;
use App\Livewire\Onboarding\Setup;
use App\Http\Controllers\TenantAccessController; 
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProfileController;

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

    // --- Settings & Administration ---
    Route::prefix('settings')->name('settings.')->group(function() {
        
        // Profile Routes (settings.profile.*)
        Route::controller(ProfileController::class)->prefix('profile')->name('profile.')->group(function() {
            Route::get('/', 'edit')->name('edit');
            Route::put('/', 'update')->name('update');
            
            // MFA Routes
            Route::post('/mfa/setup', 'setupMfa')->name('mfa.setup');
            Route::post('/mfa/enable', 'enableMfa')->name('mfa.enable');
            Route::delete('/mfa/disable', 'disableMfa')->name('mfa.disable');
            Route::post('/mfa/regenerate-codes', 'regenerateRecoveryCodes')->name('mfa.regenerate_codes');
        });

        // User Management (settings.users.*)
        Route::prefix('users')->name('users.')->group(function() {
            Route::get('/', [TenantAccessController::class, 'index'])->name('index');
            Route::post('/invite', [TenantAccessController::class, 'invite'])->name('invite');
            Route::put('/{user}', [TenantAccessController::class, 'update'])->name('update');
            Route::patch('/{user}/toggle', [TenantAccessController::class, 'toggleStatus'])->name('toggle');
        });
    }); 
    
    // Main App Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Core GL Routes
    Route::resource('accounts', AccountController::class);
    Route::resource('journals', JournalEntryController::class);
    
    // Sales & Purchases (Invoices/Bills)
    Route::resource('invoices', InvoiceController::class);
    Route::post('/invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

    // Contacts
    Route::resource('contacts', ContactController::class);
    Route::post('/contacts/{id}/restore', [ContactController::class, 'restore'])->name('contacts.restore');

    // Compliance Actions (Journals)
    Route::post('/journals/{journal}/void', [JournalEntryController::class, 'void'])->name('journals.void');
    Route::get('/journals/{journal}/reverse', [JournalEntryController::class, 'reverse'])->name('journals.reverse');
    
    // Logout Action
    Route::post('/logout', function () {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});
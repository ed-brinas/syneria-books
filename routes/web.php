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
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\OrganizationController;

// Public Route (Entry Point)
Route::get('/', Login::class)->name('login')->middleware('guest');

// Redirect legacy /login to root
Route::get('/login', function() { 
    return redirect()->route('login'); 
});

// Authenticated Routes
Route::middleware(['auth'])->group(function () {
    
    // Onboarding
    Route::get('/onboarding', Setup::class)->name('onboarding');

    // --- Settings & Administration ---
    Route::prefix('settings')->name('settings.')->group(function() {

        // Organization Settings
        Route::controller(OrganizationController::class)->prefix('organization')->name('organization.')->group(function() {
            Route::get('/', 'index')->name('index');
            Route::put('/', 'update')->name('update');
            Route::post('/logo', 'uploadLogo')->name('logo');
            Route::post('/bank', 'storeBank')->name('bank.store');
            Route::delete('/bank/{bankAccount}', 'destroyBank')->name('bank.destroy');
        });

        Route::controller(TaxRateController::class)->prefix('tax-rates')->name('tax_rates.')->group(function() {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::put('/{taxRate}', 'update')->name('update');
            Route::patch('/{taxRate}/toggle', 'toggleStatus')->name('toggle');
            Route::delete('/{taxRate}', 'destroy')->name('destroy');
        });

        Route::controller(ProfileController::class)->prefix('profile')->name('profile.')->group(function() {
            Route::get('/', 'edit')->name('edit');
            Route::put('/', 'update')->name('update');
            Route::post('/mfa/setup', 'setupMfa')->name('mfa.setup');
            Route::post('/mfa/enable', 'enableMfa')->name('mfa.enable');
            Route::delete('/mfa/disable', 'disableMfa')->name('mfa.disable');
            Route::post('/mfa/regenerate-codes', 'regenerateRecoveryCodes')->name('mfa.regenerate_codes');
        });

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
    
    // Journal Entries (Standard Resource)
    Route::resource('journals', JournalEntryController::class);
    
    // Journal Entries (Lifecycle Actions)
    Route::prefix('journals/{journal}')->name('journals.')->group(function() {
        Route::post('/submit', [JournalEntryController::class, 'submit'])->name('submit');
        Route::post('/approve', [JournalEntryController::class, 'approve'])->name('approve');
        Route::post('/reject', [JournalEntryController::class, 'reject'])->name('reject');
        Route::post('/post', [JournalEntryController::class, 'post'])->name('post');
        Route::post('/void', [JournalEntryController::class, 'void'])->name('void');
        Route::get('/reverse', [JournalEntryController::class, 'reverse'])->name('reverse');
    });
    
    // Invoice (Standard Resource)
    Route::resource('invoices', InvoiceController::class);
    
    // Invoice (Lifecycle Actions)
    Route::prefix('invoices/{invoice}')->name('invoices.')->group(function() {
        Route::post('/submit', [InvoiceController::class, 'submit'])->name('submit');
        Route::post('/approve', [InvoiceController::class, 'approve'])->name('approve');
        Route::post('/reject', [InvoiceController::class, 'reject'])->name('reject');
        Route::post('/send', [InvoiceController::class, 'send'])->name('send');
        Route::post('/void', [InvoiceController::class, 'void'])->name('void');
    });

    // Contacts
    Route::resource('contacts', ContactController::class);
    Route::post('/contacts/{id}/restore', [ContactController::class, 'restore'])->name('contacts.restore');

    // Logout
    Route::post('/logout', function () {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});
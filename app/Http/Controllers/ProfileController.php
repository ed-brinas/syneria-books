<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; 
use PragmaRX\Google2FA\Google2FA;
use App\Mail\MfaRecoveryCodes;
use App\Models\ActivityLog; // Added ActivityLog model

class ProfileController extends Controller
{
    /**
     * Show the profile edit form.
     */
    public function edit()
    {
        $user = Auth::user();
        
        // Retrieve temporary MFA setup data from session
        $secretKey = session('mfa_secret_pending');
        $qrCodeUrl = session('mfa_qr_url');
        
        // Retrieve recovery codes just generated (if any)
        $recoveryCodes = session('new_recovery_codes');

        return view('settings.profile.index', compact('user', 'secretKey', 'qrCodeUrl', 'recoveryCodes'));
    }

    /**
     * Update basic profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|max:1024',
        ]);

        if ($request->hasFile('photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            $path = $request->file('photo')->store('profile-photos', 'public');
            $user->profile_photo_path = $path;
        }

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
        ]);

        // Activity Log
        ActivityLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'action' => 'updated',
            'description' => "Updated Profile Information",
            'subject_type' => get_class($user),
            'subject_id' => $user->id,
            'ip_address' => $request->ip(),
            'properties' => [
                'fields_updated' => array_keys($request->only(['first_name', 'last_name', 'phone', 'photo']))
            ],
        ]);

        return redirect()->route('settings.profile.edit')->with('message', 'Profile updated successfully.');
    }

    /**
     * Start the MFA Setup process.
     */
    public function setupMfa()
    {
        $user = Auth::user();
        $google2fa = new Google2FA();
        $secretKey = $google2fa->generateSecretKey();
        
        $companyName = $user->tenant ? $user->tenant->company_name : 'SyneriaBooks';
        
        $otpUrl = $google2fa->getQRCodeUrl(
            $companyName,
            $user->email,
            $secretKey
        );
        
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($otpUrl);

        session(['mfa_secret_pending' => $secretKey]);
        session(['mfa_qr_url' => $qrCodeUrl]);

        return redirect()->route('settings.profile.edit');
    }

    /**
     * Complete MFA Setup: Verify code, generate recovery codes, and email them.
     */
    public function enableMfa(Request $request)
    {
        $request->validate([
            'verification_code' => 'required|digits:6',
        ]);

        $secretKey = session('mfa_secret_pending');

        if (!$secretKey) {
            return redirect()->route('settings.profile.edit')->withErrors(['verification_code' => 'Session expired. Please try setup again.']);
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($secretKey, $request->verification_code);

        if ($valid) {
            $user = Auth::user();
            
            // 1. Generate 8 Recovery Codes
            $recoveryCodes = [];
            for ($i = 0; $i < 8; $i++) {
                $recoveryCodes[] = Str::upper(Str::random(10)); // 10-char random string
            }

            // 2. Save User Data
            $user->mfa_secret = $secretKey;
            $user->mfa_enabled = true;
            $user->mfa_recovery_codes = $recoveryCodes; 
            $user->save();

            // 3. Clear setup session
            session()->forget(['mfa_secret_pending', 'mfa_qr_url']);
            
            // 4. Send Email
             try {
                if (App::environment('production')) {
                    Mail::to($user->email)->send(new MfaRecoveryCodes($recoveryCodes, $user));
                } else {
                    Log::info('Your 2MFA recovery codes:', ['codes' => $recoveryCodes]);
                }
             } catch (\Exception $e) {
                 Log::error('Failed to email recovery codes: ' . $e->getMessage());
             }

            // Activity Log
            ActivityLog::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'action' => 'enabled_mfa',
                'description' => "Enabled Two-Factor Authentication",
                'subject_type' => get_class($user),
                'subject_id' => $user->id,
                'ip_address' => $request->ip(),
            ]);

            // 5. Flash codes to session to show ONE TIME in UI
            return redirect()->route('settings.profile.edit')
                ->with('message', 'Two-Factor Authentication enabled! Please save your recovery codes below.')
                ->with('new_recovery_codes', $recoveryCodes);
        }

        session()->keep(['mfa_secret_pending', 'mfa_qr_url']);
        
        return redirect()->route('settings.profile.edit')->withErrors(['verification_code' => 'Invalid verification code.']);
    }

    /**
     * Disable MFA.
     */
    public function disableMfa()
    {
        $user = Auth::user();
        $user->mfa_enabled = false;
        $user->mfa_secret = null;
        $user->mfa_recovery_codes = null;
        $user->save();

        // Activity Log
        ActivityLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'action' => 'disabled_mfa',
            'description' => "Disabled Two-Factor Authentication",
            'subject_type' => get_class($user),
            'subject_id' => $user->id,
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('settings.profile.edit')->with('message', 'MFA has been disabled.');
    }

    /**
     * Regenerate MFA.
     */    
    public function regenerateRecoveryCodes()
    {
        $user = Auth::user();
        
        if (!$user->mfa_enabled) {
            return back()->withErrors(['mfa' => 'MFA is not active.']);
        }

        // 1. Generate 8 New Codes
        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $recoveryCodes[] = Str::upper(Str::random(10));
        }

        // 2. Update User
        $user->mfa_recovery_codes = $recoveryCodes;
        $user->save();

        // 3. Send Email
        try {
            if (App::environment('production')) {
                Mail::to($user->email)->send(new MfaRecoveryCodes($recoveryCodes, $user));
            } else {
                Log::info('Your 2MFA recovery codes:', ['codes' => $recoveryCodes]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to email regenerated codes: ' . $e->getMessage());
        }

        // Activity Log
        ActivityLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'action' => 'regenerated_codes',
            'description' => "Regenerated MFA Recovery Codes",
            'subject_type' => get_class($user),
            'subject_id' => $user->id,
            'ip_address' => request()->ip(),
        ]);

        // 4. Flash to session for UI display
        return redirect()->route('settings.profile.edit')
            ->with('message', 'Recovery codes have been regenerated and emailed to you.')
            ->with('new_recovery_codes', $recoveryCodes);
    }    
}
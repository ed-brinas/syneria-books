<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use PragmaRX\Google2FA\Google2FA;

class Login extends Component
{
    public $step = 1; // 1: Email, 2: Email OTP, 3: MFA TOTP
    public $email = '';
    public $otp = '';
    public $mfaCode = '';
    public $usingRecoveryCode = false; // Toggle for Recovery Code UI
    
    // Temporary storage for user ID between Step 2 and 3
    public $tempUserId = null; 

    protected $rules = [
        'email' => 'required|email',
    ];

    public function submitEmail()
    {
        $this->validate(['email' => 'required|email']);
        
        $key = 'login-attempt:' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Too many attempts. Please wait a moment.');
            return;
        }
        RateLimiter::hit($key);
        
        $this->generateAndSendOtp();
    }
    
    protected function generateAndSendOtp()
    {
        $code = rand(100000, 999999);
        Cache::put('otp_' . $this->email, $code, 900);
        
        try {
            // Note: Uncomment Mail class in production
            // Mail::to($this->email)->send(new LoginOtp($code)); 
            Log::info('Your OTP is: '. $code);
            
            $this->resetValidation();
            $this->step = 2;
        } catch (\Exception $e) {
            $this->addError('email', 'Could not send email.');
            logger()->error("SES Error: " . $e->getMessage());
        }
    }
    
    public function resendOtp()
    {
        $key = 'resend-otp:' . $this->email;
        if (RateLimiter::tooManyAttempts($key, 3)) {
             $this->addError('otp', 'Please wait before resending.');
             return;
        }
        RateLimiter::hit($key, 60); 

        $this->generateAndSendOtp();
        session()->flash('message', 'Code resent!');
    }

    public function verifyOtp()
    {
        $this->validate(['otp' => 'required|numeric|digits:6']);
        
        $cachedOtp = Cache::get('otp_' . $this->email);
        
        if (!$cachedOtp || $cachedOtp != $this->otp) {
            $this->addError('otp', 'Invalid or expired code.');
            return;
        }

        Cache::forget('otp_' . $this->email);

        // Find User
        $emailHash = hash_hmac('sha256', strtolower($this->email), config('app.key'));
        $user = User::where('email_hash', $emailHash)->first();

        if ($user) {
            // CHECK FOR MFA
            if ($user->mfa_enabled) {
                // If MFA enabled, move to Step 3, do NOT login yet
                $this->tempUserId = $user->id;
                
                // UX: Clear previous inputs/errors before showing MFA screen
                $this->otp = ''; 
                $this->resetValidation();
                
                $this->step = 3;
                return;
            }

            // No MFA -> Proceed to Login directly
            $this->performLogin($user);

        } else {
            // New User Registration Flow (No MFA possible yet)
            $newUser = User::create([
                'email' => $this->email,
                'last_login_at' => now(),
                'role' => 'SuperAdministrator',
                'status' => 'active',
            ]);
            
            Auth::login($newUser);
            return redirect()->route('onboarding');
        }
    }

    // Toggle between TOTP and Recovery Code
    public function toggleRecovery()
    {
        $this->usingRecoveryCode = !$this->usingRecoveryCode;
        $this->mfaCode = '';
        $this->resetValidation();
    }

    // Step 3: Verify TOTP or Recovery Code
    public function verifyMfa()
    {
        // Validation depends on method (Recovery codes are strings, TOTP is 6 digits)
        $this->validate([
            'mfaCode' => $this->usingRecoveryCode ? 'required|string' : 'required|digits:6'
        ]);

        $user = User::find($this->tempUserId);
        
        if (!$user) {
            $this->reset(); 
            return redirect()->route('login');
        }

        // Logic for Recovery Code
        if ($this->usingRecoveryCode) {
            $recoveryCodes = $user->mfa_recovery_codes;
            
            // Ensure we are working with an array (handles encrypted cast behavior)
            if (is_string($recoveryCodes)) {
                $recoveryCodes = json_decode($recoveryCodes, true);
            }

            if (is_array($recoveryCodes) && ($key = array_search($this->mfaCode, $recoveryCodes)) !== false) {
                // Valid Code: Remove it (One-time use)
                unset($recoveryCodes[$key]);
                
                // Re-save user with updated codes
                $user->mfa_recovery_codes = array_values($recoveryCodes);
                $user->save();

                $this->performLogin($user);
                return;
            }

            $this->addError('mfaCode', 'Invalid recovery code.');
            return;
        }

        // Logic for Google Authenticator (TOTP)
        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($user->mfa_secret, $this->mfaCode);

        if ($valid) {
            $this->performLogin($user);
        } else {
            $this->addError('mfaCode', 'Invalid authenticator code.');
        }
    }

    protected function performLogin($user)
    {
        Auth::login($user);
        
        if ($user->status === 'invited') {
            $user->update(['status' => 'active', 'last_login_at' => now()]);
        } else {
            $user->update(['last_login_at' => now()]);
        }
        
        if (!$user->tenant_id) {
            return redirect()->route('onboarding');
        }

        ActivityLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'action' => 'login',
            'description' => 'User logged in',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'ip_address' => request()->ip(),
        ]);
                    
        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.app', ['title' => 'Sign In - SyneriaBooks']);
    }
}
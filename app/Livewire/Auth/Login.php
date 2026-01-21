<?php

namespace App\Livewire\Auth;

use App\Mail\LoginOtp;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class Login extends Component
{
    public $step = 1; // 1: Email, 2: OTP
    public $email = '';
    public $otp = '';
    
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
    
    // Extracted method for re-use
    protected function generateAndSendOtp()
    {
        // Generate 6 digit code
        $code = rand(100000, 999999);
        
        // Cache it for 15 minutes (900 seconds) to match email text
        Cache::put('otp_' . $this->email, $code, 900);
        
        // SEND VIA AMAZON SES
        try {
            //Mail::to($this->email)->send(new LoginOtp($code)); //Uncomment on Production
            Log::info('Your OTP is: '. $code);
            $this->step = 2;
        } catch (\Exception $e) {
            $this->addError('email', 'Could not send email. Check SES config.');
            logger()->error("SES Error: " . $e->getMessage());
        }
    }
    
    public function resendOtp()
    {
        // Strict throttling for Resend (3 attempts per minute)
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

        // Generate the deterministic hash for lookup
        $emailHash = hash_hmac('sha256', $this->email, config('app.key'));

        // Look up by HASH, not email column
        $user = User::where('email_hash', $emailHash)->first();

        if ($user) {
            // Existing User -> Login
            Auth::login($user);
            
            // UPDATE LAST LOGIN
            $user->update(['last_login_at' => now()]);
            
            // If they registered but dropped off before onboarding
            if (!$user->tenant_id) {
                return redirect()->route('onboarding');
            }

            // Activity Log (Only if user belongs to a tenant)
            if ($user->tenant_id) {
                ActivityLog::create([
                    'tenant_id' => $user->tenant_id,
                    'user_id' => $user->id,
                    'action' => 'login',
                    'description' => 'User logged in via OTP',
                    'subject_type' => User::class,
                    'subject_id' => $user->id,
                    'ip_address' => request()->ip(),
                ]);
            }
                        
            return redirect()->route('dashboard');
        } else {
            // New User -> Create background account -> Redirect Onboarding
            // The User model "booted" method handles the hash generation automatically
            $newUser = User::create([
                'email' => $this->email,
                'last_login_at' => now(), // Set initial login time
                'role' => 'SuperAdministrator', // New signups are always Tenant Owners
                'status' => 'active',
            ]);
            
            Auth::login($newUser);

            return redirect()->route('onboarding');
        }
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.app', ['title' => 'Sign In - SyneriaBooks']);
    }
}
<div class="auth-wrapper bg-white">
    <div class="login-box" style="width: 100%; max-width: 400px; padding: 2rem;">
        
        <div class="text-center mb-4">
            <h3 class="fw-bold">SyneriaBooks</h3>
            <p class="text-muted">Secure Accounting Access</p>
        </div>

        <!-- Step 1: Email Input -->
        @if($step === 1)
            <form wire:submit.prevent="submitEmail">
                <div class="mb-3">
                    <label for="email" class="form-label fw-bold">Email Address</label>
                    <input type="email" wire:model="email" class="form-control form-control-lg" placeholder="name@company.com" required autofocus>
                    @error('email') <span class="text-danger small">{{ $message }}</span> @enderror
                    
                    {{-- Added Explanation for New Users --}}
                    <div class="form-text text-muted mt-2 small">
                        <i class="bi bi-info-circle me-1"></i>
                        New here? Just enter your email above. We'll create your secure account automatically.
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submitEmail">Continue</span>
                        <span wire:loading wire:target="submitEmail">Sending OTP...</span>
                    </button>
                </div>
            </form>
        @endif

        <!-- Step 2: OTP Verification -->
        @if($step === 2)
            <div class="alert alert-info py-2 small">
                <i class="bi bi-envelope-fill me-1"></i> We sent a code to <strong>{{ $email }}</strong>
            </div>

            <form wire:submit.prevent="verifyOtp">
                <div class="mb-3">
                    <label for="otp" class="form-label fw-bold">Security Code</label>
                    <input type="text" wire:model="otp" class="form-control form-control-lg text-center letter-spacing-2" placeholder="000000" maxlength="6" required autofocus>
                    @error('otp') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary btn-lg" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="verifyOtp">Verify & Sign In</span>
                        <span wire:loading wire:target="verifyOtp">Verifying...</span>
                    </button>
                </div>

                <div class="text-center">
                    <button type="button" wire:click="resendOtp" class="btn btn-link btn-sm text-decoration-none text-muted">Resend Code</button>
                </div>
            </form>
        @endif
        
        <div class="mt-4 text-center text-muted small">
            &copy; {{ date('Y') }} @yield('title', 'SyneriaBooks').
        </div>
    </div>
</div>
<div class="row justify-content-center mt-5">
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h3 class="fw-bold">Welcome</h3>
                    <p class="text-muted small">Enter your email to sign in or create an account.</p>
                </div>
                
                @if($step === 1)
                    <form wire:submit.prevent="submitEmail">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email address</label>
                            <input wire:model="email" type="email" class="form-control form-control-lg" placeholder="name@company.com" autofocus>
                            @error('email') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Continue</button>
                        </div>
                    </form>
                @else
                    <form wire:submit.prevent="verifyOtp">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Verification Code</label>
                            <div class="text-muted small mb-2">We sent a 6-digit code to {{ $email }}</div>
                            <input wire:model="otp" type="text" class="form-control form-control-lg text-center letter-spacing-2" placeholder="000000" maxlength="6" autofocus>
                            @error('otp') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        
                        <!-- Success Message -->
                        @if (session()->has('message'))
                            <div class="alert alert-success py-1 small text-center mb-3">
                                {{ session('message') }}
                            </div>
                        @endif

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">Verify & Login</button>
                        </div>
                        
                        <!-- Resend & Back Logic -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="#" wire:click.prevent="$set('step', 1)" class="text-decoration-none small text-muted">Wrong email?</a>
                            <button type="button" wire:click="resendOtp" class="btn btn-link btn-sm text-decoration-none small p-0">Resend Code</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@extends('layouts.app')

@section('title', 'My Profile - SyneriaBooks')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 text-dark fw-bold">My Profile</h2>
    </div>

    <!-- Success Message -->
    @if (session('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- IMPORTANT: Recovery Codes Display (Only shows once) -->
    @if(isset($recoveryCodes) && $recoveryCodes)
        <div class="alert alert-warning border-warning mb-4">
            <h5 class="alert-heading fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Save these Recovery Codes!</h5>
            <p class="mb-2">If you lose your phone, these codes are the <strong>only way</strong> to access your account. Store them in a safe place (like a password manager). We also emailed a copy to you.</p>
            <hr>
            <div class="row g-2">
                @foreach($recoveryCodes as $code)
                    <div class="col-md-3 col-6">
                        <div class="bg-white p-2 rounded text-center border font-monospace fw-bold user-select-all">
                            {{ $code }}
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-3">
                <button class="btn btn-sm btn-dark" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Codes</button>
            </div>
        </div>
    @endif

    <!-- Error Messages (Global) -->
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <!-- Profile Details Card -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-light py-3 border-bottom-0">
                    <h6 class="mb-0 fw-bold">Personal Details</h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('settings.profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="d-flex align-items-center mb-4">
                            <div class="me-3">
                                <img src="{{ $user->profile_photo_url }}" class="rounded-circle shadow-sm" width="80" height="80" style="object-fit: cover;">
                            </div>
                            <div>
                                <label class="form-label small text-muted mb-1">Update Photo</label>
                                <input type="file" name="photo" class="form-control form-control-sm">
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $user->first_name) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $user->last_name) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="text" class="form-control bg-light" value="{{ $user->email }}" disabled readonly>
                                <div class="form-text text-muted">Email cannot be changed securely.</div>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MFA / Security Card -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-light py-3 border-bottom-0">
                    <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-shield-lock me-2"></i>Security & MFA</h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="fw-bold d-block">Two-Factor Authentication</span>
                            <small class="text-muted">Google/Microsoft Authenticator</small>
                        </div>
                        <div>
                            @if($user->mfa_enabled)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Enabled</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2">Disabled</span>
                            @endif
                        </div>
                    </div>

                    <p class="small text-muted mb-4">
                        Protect your account by requiring a code from your authenticator app at login.
                    </p>

                    <hr class="dropdown-divider mb-4">

                    @if(!$user->mfa_enabled && !$secretKey)
                        <form action="{{ route('settings.profile.mfa.setup') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="bi bi-gear-fill me-1"></i> Setup MFA
                            </button>
                        </form>
                    @endif

                    @if($secretKey && !$user->mfa_enabled)
                        <div class="bg-light p-3 rounded-3 border mb-3">
                            <h6 class="fw-bold mb-2 small text-dark">1. Scan QR Code</h6>
                            
                            <div class="text-center mb-3 p-2 bg-white d-inline-block rounded border">
                                <img src="{{ $qrCodeUrl }}" alt="MFA QR Code" class="img-fluid" style="max-width: 150px;">
                            </div>

                            <p class="small text-muted mb-2">
                                Or enter key: <code class="fw-bold text-break user-select-all">{{ $secretKey }}</code>
                            </p>

                            <h6 class="fw-bold mt-3 mb-2 small text-dark">2. Verify Code</h6>
                            <form action="{{ route('settings.profile.mfa.enable') }}" method="POST">
                                @csrf
                                <div class="mb-2">
                                    <input type="text" name="verification_code" class="form-control form-control-sm text-center letter-spacing-2" placeholder="000000" maxlength="6" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm w-100">Activate MFA</button>
                            </form>
                        </div>
                    @endif

                    @if($user->mfa_enabled)
                        <div class="alert alert-light border mb-0">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-check-circle-fill text-success fs-5 me-2"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">MFA is active</h6>
                                    <p class="small text-muted mb-2">Your account is secured.</p>
                                    
                                    <!-- Actions -->
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#disableMfaModal">
                                            Disable MFA
                                        </button>
                                        <button type="button" class="btn btn-sm btn-link text-decoration-none" data-bs-toggle="modal" data-bs-target="#regenerateCodesModal">
                                            Regenerate Recovery Codes
                                        </button>                                    
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Disable MFA Confirmation Modal -->
<div class="modal fade" id="disableMfaModal" tabindex="-1" aria-labelledby="disableMfaModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="disableMfaModalLabel">Confirm Disable MFA</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to disable MFA? Your account will be less secure.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form action="{{ route('settings.profile.mfa.disable') }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Disable MFA</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Regenerate Codes Confirmation Modal -->
<div class="modal fade" id="regenerateCodesModal" tabindex="-1" aria-labelledby="regenerateCodesModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="regenerateCodesModalLabel">Regenerate Recovery Codes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        This will invalidate your old recovery codes. Are you sure you want to generate new ones?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form action="{{ route('settings.profile.mfa.regenerate_codes') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary">Generate New Codes</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
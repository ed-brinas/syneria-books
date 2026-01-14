{{-- resources/views/layouts/partials/nav.blade.php --}}
<!-- Navbar (Visible to Guests and Auth) -->
<nav class="navbar navbar-expand-md navbar-dark navbar-custom sticky-top shadow-sm">
    <div class="container-fluid">
        <!-- Brand / Logo -->
        <a class="navbar-brand fw-bold d-flex align-items-center" href="/">
            @if(file_exists(public_path('img/logo.png')))
                <img src="{{ asset('img/logo.png') }}" alt="SyneriaBooks" class="d-inline-block align-text-top me-2">
            @else
                <span class="fs-4 me-2">SB</span>
            @endif
            
            <span class="d-none d-md-block">
                 {{ auth()->user()?->tenant?->company_name ?? 'SyneriaBooks' }}
            </span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarCollapse">
            <!-- Main Navigation (Always Visible per request) -->
            <ul class="navbar-nav me-auto mb-2 mb-md-0">
                <li class="nav-item"><a class="nav-link active" href="#" title="Dashboard">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="#" title="Business">Business</a></li>
                <li class="nav-item"><a class="nav-link" href="#" title="Accounting">Accounting</a></li>
                <li class="nav-item"><a class="nav-link" href="#" title="Payroll">Payroll</a></li>
                <li class="nav-item"><a class="nav-link" href="#" title="Projects">Projects</a></li>
                <li class="nav-item"><a class="nav-link" href="#" title="Contacts">Contacts</a></li>
            </ul>

            <!-- Right Side Actions -->
            <ul class="navbar-nav ms-auto align-items-center">
                <!-- Quick Add -->
                <li class="nav-item">
                    <a href="#" class="nav-link" title="Create New"><i class="bi bi-plus-lg" style="font-size: 1.2rem;"></i></a>
                </li>
                
                <!-- Search Form -->
                <form class="d-flex me-2">
                        <button class="btn btn-link text-white-50" type="button"><i class="bi bi-search"></i></button>
                </form>

                <!-- Notifications -->
                <li class="nav-item me-2">
                    <a href="#" class="nav-link" title="Notifications"><i class="bi bi-bell" style="font-size: 1.2rem;"></i></a>
                </li>

                <!-- User Profile Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="bg-light text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold me-2" style="width: 30px; height: 30px; font-size: 0.8em;">
                            {{ substr(auth()->user()?->first_name ?? 'G', 0, 1) }}{{ substr(auth()->user()?->last_name ?? 'U', 0, 1) }}
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#">Profile</a></li>
                        <li><a class="dropdown-item" href="#">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">Log out</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
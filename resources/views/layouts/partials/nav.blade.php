<nav class="navbar navbar-expand-md navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">SyneriaBooks</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <ul class="navbar-nav me-auto mb-2 mb-md-0">
                 <li class="nav-item">
                     <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                 </li>
                 <li class="nav-item">
                     <a class="nav-link" href="#">Banking</a>
                 </li>
                 <li class="nav-item">
                     <a class="nav-link" href="#">Sales</a>
                 </li>
                 <li class="nav-item">
                     <a class="nav-link" href="#">Purchases</a>
                 </li>
                 
                 {{-- Phase 2: Accounting Dropdown --}}
                 <li class="nav-item dropdown">
                     <a class="nav-link dropdown-toggle {{ request()->routeIs('accounts.*') || request()->routeIs('journals.*') ? 'active' : '' }}" href="#" id="accountingDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                         Accounting
                     </a>
                     <ul class="dropdown-menu" aria-labelledby="accountingDropdown">
                         <li><a class="dropdown-item" href="{{ route('journals.index') }}">Journal Entries</a></li>
                         <li><a class="dropdown-item" href="{{ route('accounts.index') }}">Chart of Accounts</a></li>
                     </ul>
                 </li>

                 <li class="nav-item">
                     <a class="nav-link" href="#">Reports</a>
                 </li>
            </ul>

            <div class="d-flex align-items-center">
                <div class="dropdown text-end">
                    <a href="#" class="d-block link-light text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5 me-1"></i>
                        {{ Auth::user()->first_name ?? 'User' }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end text-small" aria-labelledby="userDropdown">
                        <li><h6 class="dropdown-header">{{ Auth::user()->tenant->company_name ?? 'My Organization' }}</h6></li>
                        <li><a class="dropdown-item" href="#">Settings</a></li>
                        <li><a class="dropdown-item" href="#">Billing</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Sign out</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
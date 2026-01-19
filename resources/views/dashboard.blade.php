@extends('layouts.app')

@section('title', 'Dashboard - SyneriaBooks')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1 fw-bold">{{ $tenant->company_name }}</h4>
                <p class="text-muted mb-0 small">Financial Overview • {{ now()->format('F d, Y') }}</p>
            </div>
            <div>
                {{-- Linked to Create Journal Entry for Phase 2 --}}
                <a href="{{ route('journals.create') }}" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-plus-lg me-1"></i> New Transaction
                </a>
            </div>
        </div>

        <!-- KPIs / Money In vs Out -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card h-100 border-start border-4 border-primary">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Cash Balance</h6>
                        <h3 class="fw-bold text-primary mb-0 font-monospace">₱ {{ number_format($cashBalance, 2) }}</h3>
                        <small class="text-success"><i class="bi bi-arrow-up-short"></i> 12% vs last month</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-start border-4 border-success">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Money In (This Month)</h6>
                        <h3 class="fw-bold text-success mb-0 font-monospace">₱ {{ number_format($moneyIn, 2) }}</h3>
                        <small class="text-muted">Invoices collected</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-start border-4 border-danger">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Money Out (This Month)</h6>
                        <h3 class="fw-bold text-danger mb-0 font-monospace">₱ {{ number_format($moneyOut, 2) }}</h3>
                        <small class="text-muted">Bills paid & Expenses</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-3">
            <!-- Cash Flow Chart -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
                        <h6 class="mb-0 fw-bold">Cash Flow Forecast</h6>
                        <select class="form-select form-select-sm w-auto">
                            <option>Last 6 Months</option>
                            <option>Last 12 Months</option>
                        </select>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center bg-light" style="min-height: 300px;">
                        <!-- Placeholder for Chart.js -->
                        <div class="text-center text-muted">
                            <i class="bi bi-bar-chart-line fs-1 d-block mb-2"></i>
                            <p class="small">Chart rendering requires <code>chart.js</code> integration in Phase 7.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Watchlist -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold">Account Watchlist</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-tight mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Account</th>
                                    <th class="text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($watchlist as $item)
                                <tr>
                                    <td>
                                        <span class="d-block text-truncate" style="max-width: 180px;">{{ $item['account'] }}</span>
                                    </td>
                                    <td class="text-end fw-bold font-monospace">
                                        {{ number_format($item['balance'], 2) }}
                                        @if($item['trend'] === 'up')
                                            <i class="bi bi-caret-up-fill text-success small ms-1"></i>
                                        @else
                                            <i class="bi bi-caret-down-fill text-danger small ms-1"></i>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white text-center">
                        {{-- Linked to Journal Entries List --}}
                        <a href="{{ route('journals.index') }}" class="text-decoration-none small fw-bold">View General Ledger &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
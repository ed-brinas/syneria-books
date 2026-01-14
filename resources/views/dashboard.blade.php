<x-layouts.app title="Dashboard - SyneriaBooks">
    <!-- Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2 fw-bold text-dark">Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary">Edit dashboard</button>
            </div>
        </div>
    </div>

    <!-- Top Row: Cash Flow & Accounts -->
    <div class="row g-4 mb-4">
        <!-- Cash In and Out (Chart Placeholder) -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted text-uppercase fw-bold small">Cash in and out</h6>
                    <!-- Mock Chart Area -->
                    <div class="d-flex align-items-end justify-content-center bg-light rounded" style="height: 250px;">
                        <span class="text-muted small">Chart Visualization Placeholder (Phase 2)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Watchlist -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted text-uppercase fw-bold small mb-3">Account Watchlist</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <thead class="text-muted small">
                                <tr>
                                    <th>Account</th>
                                    <th class="text-end">This Month</th>
                                    <th class="text-end">YTD</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Advertising</td>
                                    <td class="text-end fw-bold">0.00</td>
                                    <td class="text-end fw-bold">0.00</td>
                                </tr>
                                <tr>
                                    <td>Entertainment</td>
                                    <td class="text-end fw-bold">0.00</td>
                                    <td class="text-end fw-bold">0.00</td>
                                </tr>
                                <tr>
                                    <td>Inventory</td>
                                    <td class="text-end fw-bold">0.00</td>
                                    <td class="text-end fw-bold">0.00</td>
                                </tr>
                                <tr>
                                    <td>Sales</td>
                                    <td class="text-end fw-bold">0.00</td>
                                    <td class="text-end fw-bold">0.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row: Invoices & Bills -->
    <div class="row g-4">
        <!-- Invoices owed to you -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted text-uppercase fw-bold small">Invoices owed to you</h6>
                    <div class="py-4">
                        <h3 class="fw-bold mb-0">0.00</h3>
                        <p class="text-muted small">No outstanding invoices</p>
                        <a href="#" class="btn btn-primary btn-sm mt-2">New Sales Invoice</a>
                    </div>
                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between small text-muted">
                            <span>0 draft invoices</span>
                            <span>0 awaiting payment</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bills you need to pay -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted text-uppercase fw-bold small">Bills you need to pay</h6>
                    <div class="py-4">
                        <h3 class="fw-bold mb-0">0.00</h3>
                        <p class="text-muted small">No outstanding bills</p>
                        <a href="#" class="btn btn-primary btn-sm mt-2">New Bill</a>
                    </div>
                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between small text-muted">
                            <span>0 draft bills</span>
                            <span>0 awaiting payment</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
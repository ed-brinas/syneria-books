@extends('layouts.app')

@section('title', 'Journal Entries - SyneriaBooks')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 text-dark fw-bold">Journal Entries</h2>
        <a href="{{ route('journals.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> New Journal Entry
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Reference</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                        <tr>
                            <td class="ps-4">{{ $entry->date->format('Y-m-d') }}</td>
                            <td>{{ $entry->reference ?? '-' }}</td>
                            <td>
                                <div class="fw-bold">{{ $entry->description }}</div>
                                <small class="text-muted">
                                    {{ $entry->lines->count() }} lines
                                </small>
                            </td>
                            <td class="text-end fw-bold font-monospace">
                                {{ number_format($entry->total_debit, 2) }}
                            </td>
                            <td class="text-center">
                                @if($entry->status === 'posted')
                                    <span class="badge bg-success-subtle text-success">Posted</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Draft</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-album fs-1 d-block mb-2"></i>
                                No journal entries found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($entries->hasPages())
        <div class="card-footer bg-white">
            {{ $entries->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
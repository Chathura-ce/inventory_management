@extends('layouts/contentNavbarLayout')

@section('title', 'Stock History')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Stock History</h5>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('stock-history.index') }}" class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Product</label>
                    <select name="product_id" class="form-control">
                        <option value="">-- All Products --</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} ({{ $product->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Source</label>
                    <select name="source" class="form-control">
                        <option value="">All</option>
                        <option value="manual" {{ request('source') == 'manual' ? 'selected' : '' }}>Manual</option>
                        <option value="import" {{ request('source') == 'import' ? 'selected' : '' }}>Import</option>
                    </select>
                </div>

                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <a href="{{ route('stock-history.index') }}" class="btn btn-secondary w-100">Clear</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Qty</th>
                        <th>Source</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($stockEntries as $entry)
                        <tr>
                            <td>{{ $entry->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ $entry->product->name ?? 'N/A' }}</td>
                            <td>{{ $entry->product->sku ?? 'N/A' }}</td>
                            <td>{{ $entry->quantity }}</td>
                            <td>{{ ucfirst($entry->source) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No stock entries found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{ $stockEntries->withQueryString()->links() }}
        </div>
    </div>
@endsection

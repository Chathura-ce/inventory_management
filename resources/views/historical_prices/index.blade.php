@extends('layouts/contentNavbarLayout')

@section('title', 'Historical Prices')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Historical Prices - {{ $product->name }} ({{ $product->sku }})</h5>
            <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm">Back to Products</a>
        </div>

        <div class="card-body">
            @if($product->historicalPrices->isEmpty())
                <p>No historical prices recorded for this product yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Price (LKR)</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($product->historicalPrices as $price)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($price->price_date)->format('Y-m-d') }}</td>
                                <td>{{ number_format($price->narahenpita_retail, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

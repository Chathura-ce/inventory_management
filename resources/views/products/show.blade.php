@extends('layouts/contentNavbarLayout')

@section('title', 'Product Details')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5 class="mb-0">Product: {{ $product->name }}</h5>
            <a href="{{ route('products.index') }}" class="btn btn-sm btn-secondary">Back</a>
        </div>
        <div class="card-body">
            <div class="row">
                @if($product->image_path)
                    <div class="col-md-4 mb-4">
                        <img src="{{ asset('storage/' . $product->image_path) }}" class="img-fluid rounded" alt="Product Image">
                    </div>
                @endif
                <div class="col-md-8">
                    <p><strong>SKU:</strong> {{ $product->sku }}</p>
                    <p><strong>Category:</strong> {{ $product->category }}</p>
                    <p><strong>Quantity:</strong> {{ $product->quantity }}</p>
                    <p><strong>Price:</strong> ${{ number_format($product->price, 2) }}</p>
                    <p><strong>Unit:</strong> {{ $product->unit }}</p>
                    <p><strong>Min Stock Alert:</strong> {{ $product->min_stock_alert }}</p>
                    <p><strong>Max Stock:</strong> {{ $product->max_stock }}</p>
                    <p><strong>Description:</strong><br>{{ $product->description }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection

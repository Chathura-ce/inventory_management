@extends('layouts/contentNavbarLayout')

@section('title', 'Dashboard')

@section('vendor-style')
    @vite('resources/assets/vendor/libs/apex-charts/apex-charts.scss')
@endsection

@section('vendor-script')
    @vite('resources/assets/vendor/libs/apex-charts/apexcharts.js')
@endsection

@section('page-script')
    @vite('resources/assets/js/dashboards-analytics.js')
@endsection

@section('content')
    <div class="row gy-4">
        {{-- Today's Sales --}}
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Today's Sales</h5>
                    <p class="card-text fs-3">{{ number_format($todaySales,2) }} Rs</p>
                    <small>{{ $todayOrders }} orders</small>
                </div>
            </div>
        </div>

        {{-- This Week's Sales --}}
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">This Week's Sales</h5>
                    <p class="card-text fs-3">{{ number_format($weekSales,2) }} Rs</p>
                    <small>{{ $weekOrders }} orders</small>
                </div>
            </div>
        </div>

        {{-- Low Stock --}}
        <div class="col-md-3">
            <div class="card text-dark bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Low Stock Items</h5>
                    <p class="card-text fs-3">{{ $lowStockItems->count() }}</p>
                    <small>Items below reorder level</small>
                </div>
            </div>
        </div>

        {{-- Top Product --}}
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Top Product (7d)</h5>
                    @if($topProduct)
                        <p class="card-text fs-4">Product ID: {{ $topProduct->product_id }}</p>
                        <small>Revenue: {{ number_format($topProduct->revenue,2) }} Rs</small>
                    @else
                        <p class="card-text">—</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection



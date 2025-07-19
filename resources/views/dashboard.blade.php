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
    <div class="row">
        <!-- Example Card -->
        <div class="col-12 col-md-6 col-xl-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Welcome!</h5>
                    <p class="card-text">This is a clean dashboard layout. Add your widgets and content here.</p>
                    <a href="javascript:void(0)" class="btn btn-primary">Get Started</a>
                </div>
            </div>
        </div>

        <!-- Chart Example -->
        <div class="col-12 col-xl-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Analytics Chart</h5>
                </div>
                <div class="card-body">
                    <div id="totalRevenueChart"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

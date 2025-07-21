@extends('layouts/contentNavbarLayout')
@section('title','Sales Records')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Sales Records</h5>
            <a href="{{ route('sales.create') }}" class="btn btn-sm btn-primary">New Sale</a>
        </div>
        <div class="card-body">
            {{-- Filters --}}
            <form method="GET" class="row g-2 mb-4">
                <div class="col-auto">
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                </div>
                <div class="col-auto">
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control">
                </div>
                <div class="col-auto">
                    <button class="btn btn-secondary">Filter</button>
                </div>
            </form>

            <table class="table">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Items</th>
                    <th class="text-end">Total (₹)</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($sales as $s)
                    <tr>
                        <td>{{ $s->sale_date->format('Y-m-d') }}</td>
                        <td>{{ $s->items_count }}</td>
                        <td class="text-end">{{ number_format($s->total_amount,2) }}</td>
                        <td>
                            <a href="{{ route('sales.show',$s) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            {{ $sales->links() }}
        </div>
    </div>
@endsection

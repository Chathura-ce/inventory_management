@extends('layouts/contentNavbarLayout')
@section('title',"Sale #{$sale->id}")

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5 class="mb-0">Sale #{{ $sale->id }}</h5>
            <a href="{{ route('sales.receipt', $sale) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                Print Receipt
            </a>
        </div>
        <div class="card-body">
            <p><strong>Date:</strong> {{ $sale->sale_date->format('Y-m-d') }}</p>
            <table class="table">
                <thead>
                <tr>
                    <th>Item</th>
                    <th>Unit Price</th>
                    <th>Qty</th>
                    <th class="text-end">Line Total(Rs)</th>
                </tr>
                </thead>
                <tbody>
                @foreach($sale->items as $it)
                    <tr>
                        <td>{{ $it->product->name }}</td>
                        <td>{{ number_format($it->unit_price,2) }}</td>
                        <td>{{ $it->qty }}</td>
                        <td class="text-end">{{ number_format($it->line_total,2) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total</th>
                    <th class="text-end">{{ number_format($sale->total_amount,2) }}</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $sale->id }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
        th, td { border: 1px solid #333; padding: 4px; text-align: left; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
<h2>Store Name</h2>
<p>Receipt #: {{ $sale->id }}<br>
    Date: {{ $sale->sale_date->format('Y-m-d') }}</p>

<table>
    <thead>
    <tr>
        <th>Item</th>
        <th>Unit Price</th>
        <th>Qty</th>
        <th class="text-right">Total (Rs)</th>
    </tr>
    </thead>
    <tbody>
    @foreach($sale->items as $item)
        <tr>
            <td>{{ $item->product->name }}</td>
            <td>{{ number_format($item->unit_price,2) }}</td>
            <td>{{ $item->qty }}</td>
            <td class="text-right">{{ number_format($item->line_total,2) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <th colspan="3" class="text-right">Total</th>
        <th class="text-right">{{ number_format($sale->total_amount,2) }}</th>
    </tr>
    </tfoot>
</table>

<p>Thank you for your purchase!</p>
</body>
</html>



<table class="table table-bordered">
    <thead>
    <tr>
        <th>Product</th>
        <th>Unit</th>
        <th>On-Hand Qty</th>
        <th>Unit Cost</th>
        <th>Total Value</th>
    </tr>
    </thead>
    <tbody>
    @foreach($products as $p)
        <tr>
            <td>{{ $p['name'] }}</td>
            <td>{{ $p['unit'] }}</td>
            <td>{{ $p['on_hand'] }}</td>
            <td>{{ number_format($p['unit_cost'],2) }} Rs</td>
            <td>{{ number_format($p['total_value'],2) }} Rs</td>
        </tr>
    @endforeach
    </tbody>
</table>

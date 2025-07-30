

<table class="table table-bordered">
    <thead>
    <tr>
        <th>Period</th>
        <th>Orders</th>
        <th>Revenue</th>
        <th>Avg. Order Value</th>
    </tr>
    </thead>
    <tbody>
    @foreach($summary as $row)
        <tr>
            <td>{{ $row->period }}</td>
            <td>{{ $row->orders }}</td>
            <td>₹{{ number_format($row->revenue,2) }}</td>
            <td>₹{{ number_format($row->avg_order_value,2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

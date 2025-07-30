

<table class="table table-bordered">
    <thead>
    <tr>
        <th>Date</th>
        <th>Product</th>
        <th>Type</th>
        <th>Qty</th>
        <th>Balance After</th>
    </tr>
    </thead>
    <tbody>
    @foreach($movements as $m)
        <tr>
            <td>{{ \Carbon\Carbon::parse($m->date)->toDateString() }}</td>
            <td>{{ $m->product_name ?? $m->product_id }}</td>
            <td>{{ ucfirst($m->type) }}</td>
            <td>{{ $m->qty }}</td>
            <td>{{ $m->balance_after }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

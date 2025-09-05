{{-- resources/views/reports/index.blade.php --}}
@extends('layouts/contentNavbarLayout')

@section('title','Reports')

@section('content')
    <ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="sales-tab"
                    data-bs-toggle="tab" data-bs-target="#tab-sales"
                    type="button" role="tab">Sales Summary</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="balance-tab"
                    data-bs-toggle="tab" data-bs-target="#tab-balance"
                    type="button" role="tab">Stock Balance</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="movement-tab"
                    data-bs-toggle="tab" data-bs-target="#tab-movement"
                    type="button" role="tab">Stock Movement</button>
        </li>
    </ul>

    <div class="tab-content p-3">
        {{-- Sales Summary --}}
        <div class="tab-pane fade show active" id="tab-sales" role="tabpanel">
            <form id="form-sales-summary" class="row g-2 mb-3">
                <div class="col-auto">
                    <label class="form-label visually-hidden">From</label>
                    <input type="date" name="from" class="form-control" />
                </div>
                <div class="col-auto">
                    <label class="form-label visually-hidden">To</label>
                    <input type="date" name="to" class="form-control" />
                </div>
                <div class="col-auto">
                    <label class="form-label visually-hidden">Group by</label>
                    <select name="group" class="form-select">
                        <option value="day">Day</option>
                        <option value="week">Week</option>
                        <option value="month">Month</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Generate</button>
                    <a id="export-sales-summary" class="btn btn-outline-secondary ms-1">Export CSV</a>
                </div>
            </form>
            <div id="sales-summary-result">Loading…</div>
        </div>

        {{-- Stock Balance --}}
        <div class="tab-pane fade" id="tab-balance" role="tabpanel">
            <form id="form-stock-balance" class="row g-2 mb-3">
                <div class="col-auto">
                    <label class="form-label visually-hidden">As of</label>
                    <input type="date" name="as_of" class="form-control" />
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Generate</button>
                    <a id="export-stock-balance" class="btn btn-outline-secondary ms-1">Export CSV</a>
                </div>
            </form>
            <div id="stock-balance-result">Loading…</div>
        </div>

        {{-- Stock Movement --}}
        <div class="tab-pane fade" id="tab-movement" role="tabpanel">
            <form id="form-stock-movement" class="row g-2 mb-3">
                <div class="col-auto">
                    <label class="form-label visually-hidden">From</label>
                    <input type="date" name="from" class="form-control" />
                </div>
                <div class="col-auto">
                    <label class="form-label visually-hidden">To</label>
                    <input type="date" name="to" class="form-control" />
                </div>
                <div class="col-auto">
                    <label class="form-label visually-hidden">Product</label>
                    <select name="product" class="form-select">
                        <option value="">All Products</option>
                        @foreach(App\Models\Product::orderBy('name')->get() as $prod)
                            <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label visually-hidden">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="sale">Sale</option>
                        <option value="purchase">Purchase</option>
                        <option value="adjustment">Adjustment</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Generate</button>
                    <a id="export-stock-movement" class="btn btn-outline-secondary ms-1">Export CSV</a>
                </div>
            </form>
            <div id="stock-movement-result">Loading…</div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        /** helper: fetch JSON and render a table */
        async function loadAndRender(endpoint, params, containerId, headers, formatter) {
          const url = `/reports/${endpoint}?${params}`;
          // update CSV link
          document.getElementById(`export-${endpoint}`)
            .href = `${url}&export=csv`;
          // fetch JSON
          const res  = await fetch(url, { headers:{ 'Accept':'application/json' } });
          const data = await res.json();
          const rows = data[endpoint==='sales-summary'? 'summary'
            : endpoint==='stock-balance'? 'products'
              : 'movements'];
          // build HTML
          let html = '<table class="table table-bordered"><thead><tr>';
          headers.forEach(h => html += `<th>${h}</th>`);
          html += '</tr></thead><tbody>';
          rows.forEach(r => html += formatter(r));
          html += '</tbody></table>';
          document.getElementById(containerId).innerHTML = html;
        }

        // Sales Summary
        const salesForm = document.getElementById('form-sales-summary');
        salesForm.addEventListener('submit', e => {
          e.preventDefault();
          const params = new URLSearchParams(new FormData(salesForm)).toString();
          loadAndRender(
            'sales-summary', params, 'sales-summary-result',
            ['Period','Orders','Revenue','Avg Order Value'],
            r=>`<tr>
          <td>${r.period}</td>
          <td>${r.orders}</td>
          <td>${parseFloat(r.revenue).toFixed(2)}Rs</td>
          <td>${parseFloat(r.avg_order_value).toFixed(2)}Rs</td>
        </tr>`
          );
        });
        // initial load
        salesForm.dispatchEvent(new Event('submit'));

        // Stock Balance
        const balanceForm = document.getElementById('form-stock-balance');
        balanceForm.addEventListener('submit', e => {
          e.preventDefault();
          const params = new URLSearchParams(new FormData(balanceForm)).toString();
          loadAndRender(
            'stock-balance', params, 'stock-balance-result',
            ['Product','Unit','On-Hand Qty','Unit Cost','Total Value'],
            r=>`<tr>
          <td>${r.name}</td>
          <td>${r.unit}</td>
          <td>${r.on_hand}</td>
          <td>${parseFloat(r.unit_cost).toFixed(2)} Rs</td>
          <td>${parseFloat(r.total_value).toFixed(2)} Rs</td>
        </tr>`
          );
        });
        balanceForm.dispatchEvent(new Event('submit'));

        // Stock Movement
        const moveForm = document.getElementById('form-stock-movement');
        moveForm.addEventListener('submit', e => {
          e.preventDefault();
          const params = new URLSearchParams(new FormData(moveForm)).toString();
          loadAndRender(
            'stock-movement', params, 'stock-movement-result',
            ['Date','Product','Type','Qty','Balance After'],
            r=>`<tr>
          <td>${r.date}</td>
          <td>${r.product}</td>
          <td>${r.type}</td>
          <td>${r.qty}</td>
          <td>${r.balance_after}</td>
        </tr>`
          );
        });
        moveForm.dispatchEvent(new Event('submit'));
      });
    </script>
@endpush

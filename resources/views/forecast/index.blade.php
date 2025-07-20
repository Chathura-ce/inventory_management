@extends('layouts/contentNavbarLayout')

@section('title', 'Price Forecast')

@section('vendor-style')
    @vite('resources/assets/vendor/libs/apex-charts/apex-charts.scss')
@endsection

@section('vendor-script')
    @vite('resources/assets/vendor/libs/apex-charts/apexcharts.js')
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">Commodity Price Forecast</h4>

            <form id="forecastForm" class="row g-3 mb-4">
                <div class="col-md-6">
                    <select name="item" id="item" class="form-control" required>
                        <option value="">-- Select Product --</option>
                        @foreach ($products as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Forecast</button>
                </div>
            </form>

            <div id="chart" class="mb-4"></div>
            <table class="table table-bordered d-none" id="resultTable">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Predicted Price</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const chartEl = document.getElementById('chart');
        const table   = document.getElementById('resultTable');
        const tbody   = table.querySelector('tbody');
        let chart;    // ApexCharts instance

        document.getElementById('forecastForm').addEventListener('submit', async (e) => {
          e.preventDefault();

          const productId = document.getElementById('item').value;     // <select name="item">
          const steps     = document.getElementById('steps')?.value || 10; // optional <input id="steps">

          try {
            const res = await fetch('{{ route('forecast.data') }}', {
              method : 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
              },
              body: JSON.stringify({ product_id: productId, steps })
            });

            const data = await res.json();
            if (!res.ok || !data.labels) throw new Error(data.error ?? 'Bad response');

            const { labels, actual, forecast } = data;

            /* ── 1. Chart ─────────────────────────────────────── */
            if (chart) chart.destroy();
            chart = new ApexCharts(chartEl, {
              chart : { type: 'line', height: 350, toolbar: { show: false } },
              series: [
                { name: 'Actual',   data: actual },
                { name: 'Forecast', data: forecast, dashArray: 6 }
              ],
              xaxis : { categories: labels, type: 'datetime' },
              stroke: { curve: 'smooth' },
              markers: { size: 0 },
              tooltip: { shared: true },
              noData : { text: 'Loading…' }
            });
            chart.render();

            /* ── 2. Table ─────────────────────────────────────── */
            tbody.innerHTML = labels.map((d, i) => `
        <tr>
          <td>${d}</td>
          <td>${actual[i]   ?? '-'}</td>
          <td>${forecast[i] ?? '-'}</td>
        </tr>
      `).join('');
            table.classList.remove('d-none');

          } catch (err) {
            console.error(err);
            alert('Prediction failed – please try again.');
          }
        });
      });
    </script>


@endpush

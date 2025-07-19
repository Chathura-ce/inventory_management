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
                        @foreach($products as $product)
                            <option value="{{ $product }}">{{ $product }}</option>
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
      document.addEventListener('DOMContentLoaded', function () {
        const chartEl = document.getElementById('chart');
        const table = document.getElementById('resultTable');
        const tbody = table.querySelector('tbody');
        let chart;

        document.getElementById('forecastForm').addEventListener('submit', function (e) {
          e.preventDefault();
          const item = document.getElementById('item').value;

          fetch('{{ route('forecast.data') }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ item })
          })
            .then(response => response.json())
            .then(data => {
              const forecast = data.forecast;

              if (Array.isArray(forecast)) {
                const labels = forecast.map(row => row.date);
                const prices = forecast.map(row => row.price);

                // Update Chart
                if (chart) chart.destroy();
                chart = new ApexCharts(chartEl, {
                  chart: { type: 'line', height: 350 },
                  series: [{ name: item + ' Price', data: prices }],
                  xaxis: { categories: labels }
                });
                chart.render();

                // Update Table
                tbody.innerHTML = '';
                forecast.forEach(entry => {
                  tbody.innerHTML += `<tr><td>${entry.date}</td><td>${entry.price}</td></tr>`;
                });
                table.classList.remove('d-none');
              } else {
                alert('Prediction failed.');
              }
            })
            .catch(error => {
              console.error(error);
              alert('Something went wrong. Try again.');
            });
        });
      });
    </script>

@endpush

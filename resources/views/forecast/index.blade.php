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
                    <input type="hidden" id="steps" name="steps" value="4">
                    <button type="submit" class="btn btn-primary w-100">Forecast</button>
                </div>
            </form>

            <div id="chart" class="mb-4"></div>
            <div id="recommendation" class="alert alert-info d-none"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const chartEl = document.getElementById('chart');
        const recDiv  = document.getElementById('recommendation');
        let chart;

        document.getElementById('forecastForm').addEventListener('submit', async e => {
          e.preventDefault();
          const productId = document.getElementById('item').value;
          const steps     = Number(document.getElementById('steps').value);

          try {
            const res = await fetch('{{ route("forecast.data") }}', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
              },
              body: JSON.stringify({ product_id: productId, steps })
            });
            const { labels, actual, forecast } = await res.json();

            // build actual & forecast point arrays
            const actualPts = labels
              .map((d,i) => actual[i]!=null ? { x:d, y:actual[i] } : null)
              .filter(p=>p);
            const forecastPts = labels
              .map((d,i) => forecast[i]!=null ? { x:d, y:forecast[i] } : null)
              .filter(p=>p);

            // join last actual onto forecast series
            if (actualPts.length && forecastPts.length) {
              forecastPts.unshift(actualPts[actualPts.length-1]);
            }

            // render chart
            if (chart) chart.destroy();
            chart = new ApexCharts(chartEl, {
              chart:{ type:'line', height:350, toolbar:{ show:false } },
              series:[
                { name:'Actual',   data:actualPts },
                { name:'Forecast', data:forecastPts, dashArray:6, marker:{ size:6 } }
              ],
              xaxis:{ type:'datetime' },
              stroke:{ curve:'smooth' },
              markers:{ size:6 },
              tooltip:{ shared:true },
              noData:{ text:'Loading…' }
            });
            await chart.render();

            // compute recommendation
            const latestActual = actualPts[actualPts.length - 1].y;
            const nextPrice    = forecastPts[1]?.y ?? null;

            let msg;
            if (nextPrice === null) {
              msg = 'Forecast unavailable for next week.';
            } else if (nextPrice > latestActual) {
              msg = `Current price is ${latestActual.toFixed(2)}. Next week’s forecast is ${nextPrice.toFixed(2)} → price is expected to rise, so you may consider buying now.`;
            } else if (nextPrice < latestActual) {
              msg = `Current price is ${latestActual.toFixed(2)}. Next week’s forecast is ${nextPrice.toFixed(2)} → price is expected to drop, so you may consider selling or waiting.`;
            } else {
              msg = `Current price is ${latestActual.toFixed(2)}. Next week’s forecast is the same → price is stable; adjust strategy as you see fit.`;
            }

            recDiv.textContent = msg;
            recDiv.classList.remove('d-none');

          } catch (err) {
            console.error(err);
            alert('Prediction failed – please try again.');
          }
        });
      });
    </script>
@endpush


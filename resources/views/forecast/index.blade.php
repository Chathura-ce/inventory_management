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
          const steps     = Number(document.getElementById('steps').value || 30); // forecast horizon (days)

          try {
            const res = await fetch('{{ route("forecast.data") }}', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
              },
              body: JSON.stringify({
                product_id: productId,
                steps,
                display_days: 60
              })
            });

            const { labels, actual, forecast, cutoff_label, error } = await res.json();
            if (error) throw new Error(error);

            // Build (x,y) points, drop nulls
            let actualPts = labels.map((d,i)=> actual[i]!=null ? {x:d,y:actual[i]} : null).filter(Boolean);
            let forecastPts = labels.map((d,i)=> forecast[i]!=null ? {x:d,y:forecast[i]} : null).filter(Boolean);

            // Round point values to 2 decimals for display
            actualPts   = actualPts.map(p => ({ x: p.x, y: Number(p.y.toFixed(2)) }));
            forecastPts = forecastPts.map(p => ({ x: p.x, y: Number(p.y.toFixed(2)) }));

            // Continue the forecast line from last actual
            if (actualPts.length && forecastPts.length) forecastPts.unshift(actualPts[actualPts.length-1]);

            if (chart) chart.destroy();

            const annos = [];
            if (cutoff_label) {
              const xCut = new Date(cutoff_label).getTime();
              const xEnd = new Date(labels[labels.length-1]).getTime();
              annos.push({
                x: xCut, x2: xEnd, fillColor: '#f3f4f6', opacity: 0.35,
                label: { text: 'Forecast', style: { background:'#64748b' } }
              });
            }

            chart = new ApexCharts(chartEl, {
              chart:{ type:'line', height:350, toolbar:{ show:false }, zoom:{ enabled:false } },
              series:[
                { name:'Actual',   data: actualPts },
                { name:'Forecast', data: forecastPts, dashArray: 6 }
              ],
              xaxis:{ type:'datetime' },
              yaxis:{
                decimalsInFloat: 2,
                labels:{ formatter: (val) => (val==null ? '' : Number(val).toFixed(2)) }
              },
              stroke:{ curve:'smooth', width:3 },
              markers:{ size:0, hover:{ size:4 } },
              dataLabels:{ enabled:false },
              tooltip:{
                shared:true,
                x:{ format:'yyyy-MM-dd' },
                y:{ formatter: (val) => (val==null ? '' : Number(val).toFixed(2)) }
              },
              annotations:{ xaxis: annos },
              noData:{ text:'Loading…' }
            });
            await chart.render();

            // Simple next-day recommendation
            const latestActual = actualPts.length ? actualPts[actualPts.length - 1].y : null;
            const tomorrow     = forecastPts.length > 1 ? forecastPts[1].y : null; // first true future day

            let msg = 'Forecast unavailable.';
            if (latestActual != null && tomorrow != null) {
              const diff = tomorrow - latestActual;
              const pct  = latestActual !== 0 ? (diff / latestActual) * 100 : 0;
              if (diff > 0) {
                msg = `Last price ${latestActual.toFixed(2)}; tomorrow ${tomorrow.toFixed(2)} (+${pct.toFixed(1)}%). Price expected to rise → consider buying now.`;
              } else if (diff < 0) {
                msg = `Last price ${latestActual.toFixed(2)}; tomorrow ${tomorrow.toFixed(2)} (${pct.toFixed(1)}%). Price expected to fall → consider waiting / selling.`;
              } else {
                msg = `Last price ${latestActual.toFixed(2)}; tomorrow is the same → stable.`;
              }
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



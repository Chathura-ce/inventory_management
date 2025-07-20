<?php
namespace App\Http\Controllers;

use App\Models\HistoricalPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
class ForecastController extends Controller
{
    public function index()
    {
        $products = [
            1 => 'Beans',
            2 => 'Nadu Rice',
            3 => 'Dhal',
        ];

        // Later you can replace this with:
        // $products = Product::orderBy('name')->pluck('name', 'id')->toArray();

        return view('forecast.index', compact('products'));
    }



    public function getData(Request $request)
    {
        $productId    = $request->input('product_id');
        $historyWeeks = $request->input('history_weeks', 52);   // default: 1 year
        $steps        = $request->input('steps', 10);           // default: 10 wks

        /* ── 1.  Roll daily prices into weekly averages ───────────────────── */
        $daily = HistoricalPrice::query()
            ->where('product_id', $productId)
            ->whereDate('price_date', '>=', now()->subWeeks($historyWeeks))
            ->orderBy('price_date')
            ->get(['price_date', 'pettah_wholesale']);          // adjust column

        $weeklyHistory = $daily
            ->groupBy(fn ($row) => Carbon::parse($row->price_date)
                ->startOfWeek()        // Mon-Sun ISO week
                ->toDateString())
            ->map(fn ($grp) => round($grp->avg('pettah_wholesale'), 2));

        /* ── 2.  Call FastAPI and build weekly forecast map ───────────────── */
        $response = Http::timeout(180)
            ->withOptions(['verify' => false])
            ->post('https://beans-forecast-api.onrender.com/predict', [
                'steps'      => $steps,
                'product_id' => $productId,
            ]);

        if (! $response->successful()) {
            return response()->json(['error' => 'Prediction failed.'], 500);
        }

        // FastAPI returns: { "forecast": [ { "date": "...", "price": ... }, ... ] }
        $weeklyForecast = collect($response->json('forecast'))
            ->mapWithKeys(fn ($row) => [
                Carbon::parse($row['date'])
                    ->startOfWeek()          // normalise to ISO week start
                    ->toDateString() => (float) $row['price']
            ]);

        /* ── 3.  Merge history + forecast into chart-ready arrays ─────────── */
        $labels = $weeklyHistory->keys()
            ->merge($weeklyForecast->keys())
            ->unique()
            ->sort()   // chronological
            ->values();

        $actualSeries   = $labels->map(fn ($d) => $weeklyHistory[$d]   ?? null);
        $forecastSeries = $labels->map(fn ($d) => $weeklyForecast[$d] ?? null);

        return response()->json([
            'labels'   => $labels,         // ["2025-05-26", "2025-06-02", …]
            'actual'   => $actualSeries,   // [432.1, 431.7, null, 434.0, …]
            'forecast' => $forecastSeries, // [null, null, 438.2, 439.1, …]
        ]);
    }


}

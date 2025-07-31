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
            2 => 'Nadu',
            3 => 'Egg',
            4 => 'Salaya',
        ];

        // Later you can replace this with:
        // $products = Product::orderBy('name')->pluck('name', 'id')->toArray();

        return view('forecast.index', compact('products'));
    }



    public function getData(Request $request)
    {
        // 1) Map product_id → item name
        $map = [
            1 => 'Beans',
            2 => 'Nadu',
            3 => 'Egg',
            4 => 'Salaya',
        ];

        $productId    = $request->input('product_id');
        $historyWeeks = $request->input('history_weeks', 52);
        $periods      = $request->input('steps', 4);

        if (! isset($map[$productId])) {
            return response()->json(['error' => 'Invalid product_id'], 400);
        }
        $itemName = $map[$productId];

        // 2) Roll daily → weekly history
        $daily = HistoricalPrice::query()
            ->where('product_id', $productId)
            ->whereDate('price_date', '>=', now()->subWeeks($historyWeeks))
            ->orderBy('price_date')
            ->get(['price_date', 'narahenpita_retail']);

        $weeklyHistory = $daily
            ->groupBy(fn($row) => Carbon::parse($row->price_date)
                ->startOfWeek()
                ->toDateString())
            ->map(fn($grp) => round($grp->avg('narahenpita_retail'), 2));

        // 3) Build `history` payload
        $historyPayload = $weeklyHistory
            ->map(fn($price, $ds) => ['ds' => $ds, 'y' => $price])
            ->values()
            ->all();

        // 4) Call FastAPI
        $fastapi = config('services.fastapi.url');

        $response = Http::timeout(60)
            ->retry(1, 500)
            ->withOptions(['verify' => false])
            ->post("http://127.0.0.1:8001/predict", [
                'item'    => $itemName,
                'periods' => $periods,
                'history' => $historyPayload,
            ]);

        if (! $response->successful()) {
//            dd($response->body());
            return response()->json([
                'error' => 'Forecast API error ',
                'body'  => $response->body(),
            ], 500);
        }

        // 5) Parse `predictions`
        $weeklyForecast = collect($response->json('predictions'))
            ->mapWithKeys(fn($row) => [
                Carbon::parse($row['ds'])
                    ->startOfWeek()
                    ->toDateString() => (float)$row['yhat']
            ]);

// 6) Merge history + forecast keys and sort by date
        $labels = collect($weeklyHistory->keys())
            ->merge($weeklyForecast->keys())           // add the four future Mondays
            ->unique()
            ->sortBy(fn($d) => Carbon::parse($d))       // ensure chronological order
            ->values();


        $actualSeries   = $labels->map(fn($d) => $weeklyHistory[$d]   ?? null);
        $forecastSeries = $labels->map(fn($d) => $weeklyForecast[$d] ?? null);

        return response()->json([
            'labels'   => $labels,
            'actual'   => $actualSeries,
            'forecast' => $forecastSeries,
        ]);
    }




}

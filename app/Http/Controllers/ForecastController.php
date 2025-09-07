<?php

namespace App\Http\Controllers;

use App\Models\HistoricalPrice;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;           // <-- add
use Carbon\Carbon;
use Carbon\CarbonPeriod;                      // <-- add
use App\Services\PriceApiService;
use Illuminate\Http\JsonResponse;             // <-- add

class ForecastController extends Controller
{
    protected PriceApiService $priceApi;

    /*protected $products = [
        1 => 'Beans',
        // 2 => 'Nadu',
        3 => 'Egg',
        4 => 'Salaya',
        5 => 'Kelawalla',
        6 => 'Coconut',
    ];*/
    protected $products = [
        1 => 'Beans',
        // 2 => 'Nadu',
        3 => 'Egg',
        4 => 'Salaya',
        5 => 'Kelawalla',
        6 => 'Coconut',
    ];

    public function __construct(PriceApiService $priceApi)
    {
        $this->priceApi = $priceApi;
        $this->products =  Product::where('predict', 1)->pluck('name', 'id');;
    }

    public function index()
    {
//        $products = $this->products;
        $products = Product::where('predict', 1)->pluck('name', 'id');
        return view('forecast.index', compact('products'));
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $productId   = $request->input('product_id');
            $horizon     = max(1, min((int)($request->input('steps', 30)), 60)); // 1..60
            $historyDays = (int)($request->input('history_days', 1200));
            $displayDays = (int)($request->input('display_days', 60));          // <-- last 2 months

            if (!isset($this->products[$productId])) {
                return response()->json(['error' => 'Invalid product_id', 'debug' => compact('productId')], 400);
            }
            $itemName = $this->products[$productId];

            $daily = HistoricalPrice::query()
                ->where('product_id', $productId)
                ->whereDate('price_date', '>=', now()->subDays($historyDays))
                ->orderBy('price_date')
                ->get(['price_date', 'narahenpita_retail']);

            if ($daily->isEmpty()) {
                return response()->json(['error' => 'No history found', 'debug' => ['productId'=>$productId]], 404);
            }

            // ----- average per day, and treat <=0 as missing (null) for BOTH chart + payload -----
            $historyMap = $daily->groupBy(fn($r) => Carbon::parse($r->price_date)->toDateString())
                ->map(function($grp) {
                    $v = round($grp->avg('narahenpita_retail'), 2);
                    return $v > 0 ? $v : null;                            // <-- zeros -> null
                })
                ->sortKeys();

            // payload to API (drop nulls/zeros)
            $historyPayload = collect($historyMap)
                ->filter(fn($y) => $y !== null && $y > 0)
                ->map(fn($y,$ds)=>['ds'=>$ds,'y'=>(float)$y])
                ->values()->all();

            $first = Carbon::parse($historyMap->keys()->first());
            $last  = Carbon::parse($historyMap->keys()->last());
            $spanDays = $first->diffInDays($last) + 1;

            $apiUrl = rtrim(config('services.fastapi.url'), '/');
            if (!$apiUrl) {
                return response()->json(['error'=>'FASTAPI_URL not configured', 'debug'=>['services.fastapi.url'=>null]], 500);
            }

            $resp = Http::timeout(120)->retry(1, 500)->withOptions(['verify' => false])
                ->post($apiUrl.'/predict', [
                    'item'      => $itemName,
                    'history'   => $historyPayload,
                    'horizon'   => $horizon,
                    'aggregate' => 'daily',
                ]);

            $apiStatus = $resp->status();
            $apiBody   = $resp->body();
            $apiJson   = null;
            try { $apiJson = $resp->json(); } catch (\Throwable $e) {}

            if (!$resp->successful()) {
                Log::warning('Forecast API error', ['status'=>$apiStatus, 'body'=>$apiBody]);
                return response()->json([
                    'error' => 'Forecast API error',
                    'debug' => [
                        'api_url'   => $apiUrl.'/predict',
                        'api_status'=> $apiStatus,
                        'api_body'  => $apiBody,
                        'span_days' => $spanDays,
                        'first'     => $first->toDateString(),
                        'last'      => $last->toDateString(),
                        'rows_sent' => count($historyPayload),
                    ],
                ], 502);
            }

            if (is_array($apiJson) && array_key_exists('error', $apiJson)) {
                return response()->json([
                    'error' => $apiJson['error'],
                    'debug' => [
                        'api_url'   => $apiUrl.'/predict',
                        'api_status'=> $apiStatus,
                        'span_days' => $spanDays,
                        'first'     => $first->toDateString(),
                        'last'      => $last->toDateString(),
                        'rows_sent' => count($historyPayload),
                    ],
                ], 400);
            }

            $pred = collect($apiJson['predictions'] ?? []);
            if ($pred->isEmpty()) {
                return response()->json([
                    'error' => 'Empty forecast from API',
                    'debug' => [
                        'api_url'   => $apiUrl.'/predict',
                        'api_status'=> $apiStatus,
                        'span_days' => $spanDays,
                        'first'     => $first->toDateString(),
                        'last'      => $last->toDateString(),
                        'rows_sent' => count($historyPayload),
                    ],
                ], 502);
            }

            $dailyForecast = $pred->mapWithKeys(
                fn($row) => [Carbon::parse($row['ds'])->toDateString() => (float) $row['yhat']]
            );

            $firstHistory = $historyMap->keys()->first();
            $lastHistory  = $historyMap->keys()->last();
            $endDate      = Carbon::parse($lastHistory)->addDays($horizon)->toDateString();

            $labels = collect(CarbonPeriod::create($firstHistory, '1 day', $endDate))
                ->map(fn($d) => $d->toDateString())->values();

            $lastHistoryDate = Carbon::parse($lastHistory);

            // build full arrays (history nulls preserved, forecast null before cutoff)
            $actual = $labels->map(fn($ds) =>
            Carbon::parse($ds)->lte($lastHistoryDate) ? ($historyMap[$ds] ?? null) : null
            );
            $forecast = $labels->map(fn($ds) =>
            Carbon::parse($ds)->gt($lastHistoryDate) ? ($dailyForecast[$ds] ?? null) : null
            );

            // ---- slice to last 60 history days + all forecast ----
            $displayStart = Carbon::parse($lastHistory)->subDays(max(0, $displayDays - 1))->toDateString();
            $startIdx = $labels->search($displayStart);
            if ($startIdx === false) {
                $startIdx = max(0, $labels->count() - ($displayDays + $horizon));
            }

            $labels   = $labels->slice($startIdx)->values();
            $actual   = $actual->slice($startIdx)->values();
            $forecast = $forecast->slice($startIdx)->values();

            $cutoffLabel = $lastHistoryDate->copy()->addDay()->toDateString();
            $cutoffIndex = $labels->search($cutoffLabel);

            return response()->json([
                'labels'       => $labels->all(),
                'actual'       => $actual->all(),
                'forecast'     => $forecast->all(),
                'cutoff_label' => $cutoffLabel,
                'cutoff_index' => $cutoffIndex,
            ]);

        } catch (\Throwable $e) {
            Log::error('Forecast endpoint failed', ['exception' => $e]);
            return response()->json([
                'error' => 'Server error',
                'debug' => ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()],
            ], 500);
        }
    }

    function accuracy()
    {
        $id = 10;
        $history = HistoricalPrice::where('product_id', $id)
            ->orderBy('price_date')
            ->pluck('narahenpita_retail', 'price_date');

        // need at least 760 days (730 + 30)
        if ($history->count() >= 760) {
            // take last 760 days
            $last760 = $history->slice($history->count() - 760);

            // split: first 730 for input, last 30 for evaluation
            $trainWindow = $last760->slice(0, 730);
            $expected    = $last760->slice(730); // 30 days

            // prepare API payload
            $historyPayload = collect($trainWindow)->map(
                fn($y,$ds)=>['ds'=>$ds,'y'=>(float)$y]
            )->values()->all();

            $predictions = $this->priceApi->predict($historyPayload);

            // ensure expected is array with numeric keys
            $expectedArr = array_values($expected->toArray());

            $metrics = $this->calculateMetrics($expectedArr, $predictions);

            return ['last_accuracy' => 100-$metrics['mape']];
        }
    }

    function calculateMetrics(array $actual, array $predicted): array
    {
        $n = count($actual);

        if ($n === 0 || $n !== count($predicted)) {
            return [
                'mae'   => null,
                'rmse'  => null,
                'mape'  => null,
                'smape' => null,
            ];
        }

        $mae = 0.0;
        $rmse = 0.0;
        $mape = 0.0;
        $smape = 0.0;

        foreach ($actual as $i => $yTrue) {
            $yPred = $predicted[$i];

            $error = $yTrue - $yPred;

            // Mean Absolute Error
            $mae += abs($error);

            // Root Mean Squared Error
            $rmse += pow($error, 2);

            // Mean Absolute Percentage Error (skip zero actuals)
            if ($yTrue != 0) {
                $mape += abs($error / $yTrue);
            }

            // Symmetric MAPE (skip both zero actual+pred)
            if (($yTrue + $yPred) != 0) {
                $smape += abs($yPred - $yTrue) / (($yTrue + $yPred) / 2.0);
            }
        }

        return [
            'mae'   => $mae / $n,
            'rmse'  => sqrt($rmse / $n),
            'mape'  => ($mape / $n) * 100,   // percentage
            'smape' => ($smape / $n) * 100, // percentage
        ];
    }
}

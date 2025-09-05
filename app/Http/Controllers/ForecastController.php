<?php

namespace App\Http\Controllers;

use App\Models\HistoricalPrice;
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
    }

    public function index()
    {
        $products = $this->products;
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

}

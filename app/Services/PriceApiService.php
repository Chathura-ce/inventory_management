<?php

namespace App\Services;

use App\Models\HistoricalPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PriceApiService
{
    protected string $baseUrl;

    public function __construct()
    {
        // e.g. https://yourapp.fly.dev
//        $this->baseUrl = config('services.price_api.url');
        $this->baseUrl = 'http://127.0.0.1:8003';
    }

    /**
     * Fetch and cache prices for a specific date.
     * Returns array of ['product_id','price_date','narahenpita_retail']
     */
    public function getPricesForDate(Carbon $date): array
    {
        $dateStr = $date->toDateString();

        // Call external API
        try {
            $resp = Http::get("{$this->baseUrl}/prices", ['date' => $dateStr]);
            if (! $resp->successful()) {
                Log::warning("Price API call failed for {$dateStr}: {$resp->status()}");
                return [];
            }
            $items = $resp->json();
        } catch (\Exception $e) {
            Log::error("PriceApiService getPricesForDate exception for {$dateStr}: {$e->getMessage()}");
            return [];
        }

        // Upsert into DB
        foreach ($items as $item) {
            HistoricalPrice::updateOrCreate(
                [
                    'product_id' => $item['product_id'],
                    'price_date' => $item['price_date'],
                ],
                ['narahenpita_retail' => $item['narahenpita_retail']]
            );
        }

        return $items;
    }

    /**
     * Ensure cache from $start to $end inclusive, skipping errors.
     */
    public function ensureCacheUpToDate(Carbon $start, Carbon $end): void
    {
        for ($d = $start->copy(); $d->lte($end); $d->addWeek()) {
            try {
                $dateStr = $d->toDateString();
                $exists = HistoricalPrice::where('price_date', $dateStr)->exists();
                if (! $exists) {
                    Log::info("PriceApiService caching date {$dateStr}");
                    $this->getPricesForDate($d);
                }
            } catch (\Exception $e) {
                Log::error("PriceApiService ensureCacheUpToDate error on {$d->toDateString()}: {$e->getMessage()}");
                continue;
            }
        }
    }

    public function predict(array $history, int $horizon = 30, string $aggregate = 'daily'): array
    {
        $apiUrl = rtrim(config('services.fastapi.url'), '/');

        $resp = Http::timeout(120)->retry(1, 500)->withOptions(['verify' => false])
            ->post($apiUrl.'/predict', [
                'item'      => 'Test',
                'history'   => $history,
                'horizon'   => $horizon,
                'aggregate' => $aggregate,
            ]);

        if (!$resp->successful()) {
            throw new \Exception("Forecast API error: ".$resp->status()." ".$resp->body());
        }

        return collect($resp->json()['predictions'] ?? [])
            ->pluck('yhat')
            ->all();
    }
}

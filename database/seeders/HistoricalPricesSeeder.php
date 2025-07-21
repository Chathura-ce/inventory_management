<?php

namespace Database\Seeders;

use App\Models\HistoricalPrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class HistoricalPricesSeeder extends Seeder
{
    /**
     * Hard‑coded mapping between the exact
     * item names found in all_data.csv and the
     * already‑existing product_id values in the `products` table.
     *
     * 👉  Update this array once (or keep it in a config file) and
     *     you can re‑seed safely at any time.
     */
    private array $productMap = [
        // 'CSV Item Name' => product_id,
        'Beans'            => 1,
        'Nadu'           => 2,
        'Egg'           => 3,
        'Salaya'           => 4,
    ];

    /**
     * Path to the CSV inside database/seeders/data
     */
    private string $csvPath = 'database/seeders/data/all_data.csv';

    /**
     * Number of rows to buffer before doing an upsert.
     */
    private int $chunkSize = 1000;

    public function run(): void
    {
        HistoricalPrice::truncate();
        if (! File::exists(base_path($this->csvPath))) {
            $this->command->error("CSV file not found: {$this->csvPath}");
            return;
        }

        if (empty($this->productMap)) {
            $this->command->error('Product map is empty. Please populate $productMap with CSV names => product_id.');
            return;
        }

        $handle = fopen(base_path($this->csvPath), 'r');
        $header = fgetcsv($handle); // e.g. item,report_date,pettah_wholesale_today,narahenpita_retail_today

        $buffer = [];
        $total  = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $row       = array_combine($header, $data);
            $itemName  = $row['item'] ?? null;

            if (! isset($this->productMap[$itemName])) {
                $this->command->warn("Skipping unknown product: {$itemName}");
                continue; // ignore rows with unmapped items
            }

            $buffer[] = [
                'product_id'        => $this->productMap[$itemName],
                'price_date'        => Carbon::parse($row['report_date'])->toDateString(),
                'pettah_wholesale'  => $row['pettah_wholesale_today'] ?: null,
                'narahenpita_retail'=> $row['narahenpita_retail_today'] ?: null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];

            if (count($buffer) >= $this->chunkSize) {
                $this->flush($buffer);
                $total += $this->chunkSize;
                $buffer = [];
            }
        }

        // Flush remainder
        if ($buffer) {
            $this->flush($buffer);
            $total += count($buffer);
        }

        fclose($handle);
        $this->command->info("Seeded historical prices: {$total} rows");
    }

    /**
     * Perform a bulk upsert using a single query per chunk.
     */
    private function flush(array $rows): void
    {
        DB::transaction(function () use ($rows) {
            HistoricalPrice::upsert(
                $rows,
                ['product_id', 'price_date'], // unique by these
                ['pettah_wholesale', 'narahenpita_retail', 'updated_at']
            );
        });
    }
}

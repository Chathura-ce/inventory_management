<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Static master list – one row per real commodity.
     * Tweak any numbers or add rows as you like.
     */
    private array $items = [
        [
            'name'        => 'Beans',
            'sku'         => 'BEAN-001',
            'unit'        => 'kg',
            'category'    => 'Vegetables',
            'quantity'    => 50,
            'price'       => 450.00,
            'min_stock'   => 10,
            'max_stock'   => 200,
            'description' => 'Fresh green beans sourced from local farms.',
            'image'       => null,
        ],
        [
            'name'        => 'Nadu Rice',
            'sku'         => 'NADU-001',
            'unit'        => 'kg',
            'category'    => 'Grains & Cereals',
            'quantity'    => 120,
            'price'       => 225.00,
            'min_stock'   => 30,
            'max_stock'   => 500,
            'description' => 'Popular medium-grain Nadu rice.',
            'image'       => null,
        ],
        [
            'name'        => 'Egg',
            'sku'         => 'EGG-001',
            'unit'        => 'pcs',
            'category'    => 'Eggs & Dairy',   // adjust if you renamed
            'quantity'    => 600,
            'price'       => 37.00,
            'min_stock'   => 100,
            'max_stock'   => 2000,
            'description' => 'Standard white-shell chicken egg.',
            'image'       => null,
        ],
        [
            'name'        => 'Salaya',
            'sku'         => 'SAL-001',
            'unit'        => 'kg',
            'category'    => 'Fish',
            'quantity'    => 75,
            'price'       => 650.00,
            'min_stock'   => 15,
            'max_stock'   => 300,
            'description' => 'Fresh Salaya fish – cleaned and iced.',
            'image'       => null,
        ],
        // ── NEW ITEMS ───────────────────────────────────────────────
        [
            'name'        => 'Kelawalla',
            'sku'         => 'KEL-001',
            'unit'        => 'kg',
            'category'    => 'Fish',
            'quantity'    => 40,
            'price'       => 1200.00,
            'min_stock'   => 10,
            'max_stock'   => 150,
            'description' => 'Kelawalla (Yellowfin tuna) – freshly chilled.',
            'image'       => null,
        ],
        [
            'name'        => 'Coconut',
            'sku'         => 'COC-001',
            'unit'        => 'pcs',
            'category'    => 'Oils & Fats',
            'quantity'    => 300,
            'price'       => 85.00,
            'min_stock'   => 50,
            'max_stock'   => 1000,
            'description' => 'Whole mature coconuts for retail sale.',
            'image'       => null,
        ],
    ];


    public function run(): void
    {
        foreach ($this->items as $row) {
            Product::updateOrCreate(
                ['sku' => $row['sku']],   // lookup key
                [
                    'name'            => $row['name'],
                    'unit'            => $row['unit'],
                    'category_id'     => Category::where('name', $row['category'])->first()->id,
                    'quantity'        => $row['quantity'],
                    'price'           => $row['price'],
                    'description'     => $row['description'],
                    'min_stock_alert' => $row['min_stock'],
                    'max_stock'       => $row['max_stock'],
                    'image_path'      => $row['image'],
                    // add more columns here if your table has them
                ]
            );
        }
    }
}

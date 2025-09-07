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
            'category'    => 'Eggs & Dairy',
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
        // New items
        [
            'name'        => 'Carrot',
            'sku'         => 'CAR-007',
            'unit'        => 'kg',
            'category'    => 'Vegetables',
            'quantity'    => 70,
            'price'       => 180.00,
            'min_stock'   => 15,
            'max_stock'   => 250,
            'description' => 'Fresh orange carrots, ideal for cooking and salads.',
            'image'       => null,
        ],
        [
            'name'        => 'Cabbage',
            'sku'         => 'CAB-008',
            'unit'        => 'kg',
            'category'    => 'Vegetables',
            'quantity'    => 80,
            'price'       => 160.00,
            'min_stock'   => 20,
            'max_stock'   => 300,
            'description' => 'Green cabbage, fresh and crisp.',
            'image'       => null,
        ],
        [
            'name'        => 'Tomato',
            'sku'         => 'TOM-009',
            'unit'        => 'kg',
            'category'    => 'Vegetables',
            'quantity'    => 90,
            'price'       => 200.00,
            'min_stock'   => 20,
            'max_stock'   => 350,
            'description' => 'Ripe red tomatoes for cooking and salads.',
            'image'       => null,
        ],
        [
            'name'        => 'Brinjal',
            'sku'         => 'BRI-010',
            'unit'        => 'kg',
            'category'    => 'Vegetables',
            'quantity'    => 60,
            'price'       => 190.00,
            'min_stock'   => 15,
            'max_stock'   => 220,
            'description' => 'Fresh purple brinjals (eggplants).',
            'image'       => null,
        ],
        [
            'name'        => 'Pumpkin',
            'sku'         => 'PUM-011',
            'unit'        => 'kg',
            'category'    => 'Vegetables',
            'quantity'    => 50,
            'price'       => 140.00,
            'min_stock'   => 10,
            'max_stock'   => 200,
            'description' => 'Locally grown pumpkin, sweet and fresh.',
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

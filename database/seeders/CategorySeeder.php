<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
//        $categories = ['Rice', 'Dhal', 'Sugar', 'Beans', 'Flour', 'Salt','Fish'];
        $categories = ['Grains & Cereals', 'Pulses & Legumes', 'Sugar & Sweeteners', 'Eggs & Dairy', 'Vegetables', 'Fish','Oils & Fats','Meat & Poultry','Tea'];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}

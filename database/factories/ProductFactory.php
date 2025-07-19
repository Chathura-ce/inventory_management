<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'sku' => strtoupper(Str::random(8)),
            'category_id' => Category::inRandomOrder()->first()?->id ?? null,
            'quantity' => $this->faker->numberBetween(10, 100),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'description' => $this->faker->sentence(),
            'unit' => $this->faker->randomElement(['pcs', 'box', 'kg']),
            'min_stock_alert' => $this->faker->numberBetween(5, 20),
            'max_stock' => $this->faker->numberBetween(100, 200),
            'image_path' => null,
        ];
    }
}

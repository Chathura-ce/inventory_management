<?php

namespace Database\Seeders;

use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        if (!User::where('email', 'admin@admin.com')->exists()) {
            // If they don't exist, create them
            User::factory()->create([
                'name' => 'Admin',
                'email' => 'admin@admin.com',
            ]);
        }

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            HistoricalPricesSeeder::class,
        ]);
    }
}

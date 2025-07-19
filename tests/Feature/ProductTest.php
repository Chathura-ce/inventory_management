<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;
    public function test_products_page_loads_for_authenticated_user(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->get('/products');

        $response->assertStatus(200);
    }
    public function test_authenticated_user_can_view_product_list(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Product::factory()->count(5)->create();

        $response = $this->get('/products');

        $response->assertStatus(200);
        $response->assertSee('Products');
    }

    public function test_user_can_create_product(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $category = Category::factory()->create();

        $data = [
            'name' => 'Test Product',
            'sku' => 'TP123',
            'category_id' => $category->id,
            'quantity' => 50,
            'price' => 99.99,
            'unit' => 'pcs',
            'min_stock_alert' => 10,
            'max_stock' => 100,
            'description' => 'Test description',
        ];

        $response = $this->post('/products', $data);

        $response->assertRedirect('/products');
        $this->assertDatabaseHas('products', ['sku' => 'TP123']);
    }

    public function test_user_can_update_product(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();
        $category = Category::factory()->create();

        $response = $this->put("/products/{$product->id}", [
            'name' => 'Updated Name',
            'sku' => $product->sku,
            'category_id' => $category->id,
            'quantity' => 80,
            'price' => 120,
            'unit' => $product->unit,
            'min_stock_alert' => 5,
            'max_stock' => 150,
            'description' => 'Updated',
        ]);

        $response->assertRedirect('/products');
        $this->assertDatabaseHas('products', ['name' => 'Updated Name']);
    }

    public function test_user_can_delete_product(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();

        $response = $this->delete("/products/{$product->id}");

        $response->assertRedirect('/products');
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_image_upload_works(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('product.jpg');
        $category = Category::factory()->create();

        $response = $this->post('/products', [
            'name' => 'With Image',
            'sku' => 'IMG123',
            'quantity' => 10,
            'price' => 25.5,
            'category_id' => $category->id,
            'image' => $file
        ]);

        $response->assertRedirect('/products');
        Storage::disk('public')->assertExists('products/' . $file->hashName());
    }
}

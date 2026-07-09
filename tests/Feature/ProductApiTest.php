<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Events\LowStockAlert;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear cache before each test to prevent side effects
        Cache::clear();
    }

    /**
     * Test products listing (paginated).
     */
    public function test_can_list_products_with_pagination(): void
    {
        Product::factory()->count(20)->create();

        $response = $this->getJson('/api/products?per_page=15');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'sku',
                        'name',
                        'description',
                        'price',
                        'stock_quantity',
                        'low_stock_threshold',
                        'status',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'meta' => [
                    'pagination' => [
                        'total',
                        'count',
                        'per_page',
                        'current_page',
                        'total_pages',
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(15, $response->json('data'));
        $this->assertEquals(20, $response->json('meta.pagination.total'));
    }

    /**
     * Test retrieving a single product.
     */
    public function test_can_get_single_product(): void
    {
        $product = Product::factory()->create([
            'sku' => 'SKU-TEST-123',
            'name' => 'Test Product',
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'sku' => 'SKU-TEST-123',
                    'name' => 'Test Product',
                ]
            ]);
    }

    /**
     * Test product creation validation.
     */
    public function test_cannot_create_product_without_required_fields(): void
    {
        $response = $this->postJson('/api/products', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    'sku',
                    'name',
                    'price',
                    'stock_quantity',
                    'status',
                ]
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Validation errors occurred.',
            ]);
    }

    /**
     * Test creating a product.
     */
    public function test_can_create_product(): void
    {
        $data = [
            'sku' => 'NEW-SKU-999',
            'name' => 'Awesome Wireless Mouse',
            'description' => 'Top-tier ergonomics wireless mouse.',
            'price' => 49.99,
            'stock_quantity' => 100,
            'low_stock_threshold' => 15,
            'status' => 'active',
        ];

        $response = $this->postJson('/api/products', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'sku' => 'NEW-SKU-999',
                    'name' => 'Awesome Wireless Mouse',
                    'price' => '49.99',
                    'stock_quantity' => 100,
                    'low_stock_threshold' => 15,
                    'status' => 'active',
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'NEW-SKU-999',
            'name' => 'Awesome Wireless Mouse',
        ]);
    }

    /**
     * Test updating a product.
     */
    public function test_can_update_product(): void
    {
        $product = Product::factory()->create([
            'sku' => 'SKU-OLD-111',
            'name' => 'Old Product Name',
        ]);

        $response = $this->putJson("/api/products/{$product->id}", [
            'name' => 'Updated Product Name',
            'price' => 19.99,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'sku' => 'SKU-OLD-111',
                    'name' => 'Updated Product Name',
                    'price' => '19.99',
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
        ]);
    }

    /**
     * Test soft deleting a product.
     */
    public function test_can_soft_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product deleted successfully.',
            ]);

        // Verify soft deleted in DB (deleted_at is set)
        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);

        // Attempting to fetch it should now return 404
        $this->getJson("/api/products/{$product->id}")
            ->assertStatus(404);
    }

    /**
     * Test adjusting stock quantity and event trigger.
     */
    public function test_can_adjust_stock_and_dispatches_event(): void
    {
        Event::fake();

        $product = Product::factory()->create([
            'stock_quantity' => 15,
            'low_stock_threshold' => 10,
        ]);

        // Decrement by 8 -> stock becomes 7 (below threshold 10)
        $response = $this->postJson("/api/products/{$product->id}/stock", [
            'amount' => -8,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'stock_quantity' => 7,
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 7,
        ]);

        Event::assertDispatched(LowStockAlert::class, function ($event) use ($product) {
            return $event->product->id === $product->id;
        });
    }

    /**
     * Test that stock cannot go below zero.
     */
    public function test_cannot_adjust_stock_below_zero(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 5,
        ]);

        // Try to decrement by 10 -> would result in -5
        $response = $this->postJson("/api/products/{$product->id}/stock", [
            'amount' => -10,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Stock quantity cannot drop below zero.',
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 5,
        ]);
    }

    /**
     * Test listing products below stock threshold.
     */
    public function test_can_list_low_stock_products(): void
    {
        // Create 3 normal products
        Product::factory()->count(3)->create([
            'stock_quantity' => 20,
            'low_stock_threshold' => 10,
        ]);

        // Create 2 low-stock products
        Product::factory()->count(2)->lowStock()->create([
            'low_stock_threshold' => 10,
        ]);

        $response = $this->getJson('/api/products/low-stock');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJson([
                'success' => true,
            ]);
    }
}

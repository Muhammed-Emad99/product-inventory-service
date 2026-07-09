<?php

namespace App\Repositories;

use App\Events\LowStockAlert;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentProductRepository implements ProductRepositoryInterface
{
    /**
     * List all products with pagination.
     */
    public function all(int $perPage = 15): LengthAwarePaginator
    {
        return Product::orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get a single product by UUID.
     */
    public function find(string $id): Product
    {
        return Product::findOrFail($id);
    }

    /**
     * Create a new product.
     */
    public function create(array $data): Product
    {
        $product = Product::create($data);

        // Check if stock starts below threshold
        if ($product->isLowStock()) {
            event(new LowStockAlert($product));
        }

        return $product;
    }

    /**
     * Update an existing product.
     */
    public function update(string $id, array $data): Product
    {
        $product = $this->find($id);
        $product->update($data);

        // Check if updating values caused it to go below stock threshold
        if ($product->isLowStock()) {
            event(new LowStockAlert($product));
        }

        return $product;
    }

    /**
     * Soft delete a product.
     */
    public function delete(string $id): bool
    {
        $product = $this->find($id);
        return $product->delete();
    }

    /**
     * Adjust stock level (positive increment, negative decrement).
     * Prevents race conditions using DB lock for update.
     */
    public function adjustStock(string $id, int $amount): Product
    {
        return DB::transaction(function () use ($id, $amount) {
            $product = Product::lockForUpdate()->findOrFail($id);

            $newQuantity = $product->stock_quantity + $amount;
            if ($newQuantity < 0) {
                throw new \InvalidArgumentException("Stock quantity cannot drop below zero.");
            }

            $product->stock_quantity = $newQuantity;
            $product->save();

            // If new quantity falls below threshold, dispatch alert event
            if ($product->isLowStock()) {
                event(new LowStockAlert($product));
            }

            return $product;
        });
    }

    /**
     * Get products below stock threshold.
     */
    public function getLowStock(int $perPage = 15): LengthAwarePaginator
    {
        return Product::whereRaw('stock_quantity < low_stock_threshold')
            ->orderBy('stock_quantity', 'asc')
            ->paginate($perPage);
    }
}

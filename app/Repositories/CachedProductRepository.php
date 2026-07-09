<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class CachedProductRepository implements ProductRepositoryInterface
{
    /**
     * The underlying Eloquent repository being decorated.
     */
    protected ProductRepositoryInterface $delegate;

    public function __construct(ProductRepositoryInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * Get or initialize the listing cache version.
     */
    protected function getCacheVersion(): int
    {
        return (int) Cache::remember('products:version', 86400, fn () => 1);
    }

    /**
     * Increment the listing cache version to instantly invalidate cached list pages.
     */
    protected function incrementCacheVersion(): void
    {
        try {
            Cache::increment('products:version');
        } catch (\Throwable $e) {
            // Fallback to timestamp if the current cache store doesn't support increment
            Cache::put('products:version', time(), 86400);
        }
    }

    /**
     * List all products with pagination (Cached).
     */
    public function all(int $perPage = 15): LengthAwarePaginator
    {
        $page = request()->get('page', 1);
        $version = $this->getCacheVersion();
        $cacheKey = "products:v:{$version}:page:{$page}:limit:{$perPage}";

        return Cache::remember($cacheKey, 3600, fn () => $this->delegate->all($perPage));
    }

    /**
     * Get a single product by UUID (Cached).
     */
    public function find(string $id): Product
    {
        return Cache::remember("product:{$id}", 3600, fn () => $this->delegate->find($id));
    }

    /**
     * Create a new product and invalidate listing cache.
     */
    public function create(array $data): Product
    {
        $product = $this->delegate->create($data);
        $this->incrementCacheVersion();
        return $product;
    }

    /**
     * Update product and invalidate cache.
     */
    public function update(string $id, array $data): Product
    {
        $product = $this->delegate->update($id, $data);
        Cache::forget("product:{$id}");
        $this->incrementCacheVersion();
        return $product;
    }

    /**
     * Soft delete product and invalidate cache.
     */
    public function delete(string $id): bool
    {
        $result = $this->delegate->delete($id);
        Cache::forget("product:{$id}");
        $this->incrementCacheVersion();
        return $result;
    }

    /**
     * Adjust stock level and invalidate cache.
     */
    public function adjustStock(string $id, int $amount): Product
    {
        $product = $this->delegate->adjustStock($id, $amount);
        Cache::forget("product:{$id}");
        $this->incrementCacheVersion();
        return $product;
    }

    /**
     * Get products below stock threshold (Cached).
     */
    public function getLowStock(int $perPage = 15): LengthAwarePaginator
    {
        $page = request()->get('page', 1);
        $version = $this->getCacheVersion();
        $cacheKey = "products:low_stock:v:{$version}:page:{$page}:limit:{$perPage}";

        return Cache::remember($cacheKey, 3600, fn () => $this->delegate->getLowStock($perPage));
    }
}

<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    /**
     * List all products with pagination.
     */
    public function all(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a single product by UUID.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(string $id): Product;

    /**
     * Create a new product.
     */
    public function create(array $data): Product;

    /**
     * Update an existing product.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(string $id, array $data): Product;

    /**
     * Soft delete a product.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(string $id): bool;

    /**
     * Adjust stock level (positive increment, negative decrement).
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException if stock goes below zero
     */
    public function adjustStock(string $id, int $amount): Product;

    /**
     * Get products below stock threshold.
     */
    public function getLowStock(int $perPage = 15): LengthAwarePaginator;
}

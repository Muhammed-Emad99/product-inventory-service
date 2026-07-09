<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustStockRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Repositories\ProductRepositoryInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse;

    protected ProductRepositoryInterface $productRepository;

    /**
     * Inject ProductRepositoryInterface.
     */
    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * List all products (with pagination).
     * GET /api/products
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $products = $this->productRepository->all($perPage);

        return $this->successResponse($products, 'Products retrieved successfully.');
    }

    /**
     * Get single product by UUID.
     * GET /api/products/{id}
     */
    public function show(string $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        return $this->successResponse($product, 'Product retrieved successfully.');
    }

    /**
     * Create new product.
     * POST /api/products
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productRepository->create($request->validated());

        return $this->successResponse($product, 'Product created successfully.', 201);
    }

    /**
     * Update product.
     * PUT /api/products/{id}
     */
    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $product = $this->productRepository->update($id, $request->validated());

        return $this->successResponse($product, 'Product updated successfully.');
    }

    /**
     * Soft delete product.
     * DELETE /api/products/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $this->productRepository->delete($id);

        return $this->successResponse(null, 'Product deleted successfully.');
    }

    /**
     * Adjust stock (increment/decrement).
     * POST /api/products/{id}/stock
     */
    public function adjustStock(AdjustStockRequest $request, string $id): JsonResponse
    {
        try {
            $amount = (int) $request->input('amount');
            $product = $this->productRepository->adjustStock($id, $amount);

            return $this->successResponse($product, 'Stock adjusted successfully.');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * List products below threshold.
     * GET /api/products/low-stock
     */
    public function lowStock(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $products = $this->productRepository->getLowStock($perPage);

        return $this->successResponse($products, 'Low stock products retrieved successfully.');
    }
}

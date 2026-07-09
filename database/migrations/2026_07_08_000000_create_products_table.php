<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function run(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sku');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->integer('stock_quantity');
            $table->integer('low_stock_threshold')->default(10);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique('sku');
            $table->index('status');
        });

        // PostgreSQL-specific partial index for low stock optimization
        if (config('database.default') === 'pgsql') {
            DB::statement('CREATE INDEX products_low_stock_idx ON products (id) WHERE (deleted_at IS NULL AND stock_quantity < low_stock_threshold);');
        } else {
            // Fallback composite index for SQLite/MySQL in-memory testing
            Schema::table('products', function (Blueprint $table) {
                $table->index(['stock_quantity', 'low_stock_threshold']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

<?php

namespace App\Listeners;

use App\Events\LowStockAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendLowStockNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(LowStockAlert $event): void
    {
        Log::warning(sprintf(
            "Low Stock Alert: Product '%s' (SKU: %s, ID: %s) has fallen below its low stock threshold of %d. Current stock: %d.",
            $event->product->name,
            $event->product->sku,
            $event->product->id,
            $event->product->low_stock_threshold,
            $event->product->stock_quantity
        ));
    }
}

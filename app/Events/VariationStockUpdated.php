<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class VariationStockUpdated implements ShouldBroadcastNow
{
    public function __construct(
        public int $variationId,
        public int $quantity
    ) {}

    public function broadcastOn()
    {

        return new Channel('variation-stock');
    }

    public function broadcastAs()
    {
        return 'stock.updated';
    }
}

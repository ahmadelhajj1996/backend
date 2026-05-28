<?php

namespace App\Jobs;

use App\Services\VariationPriceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateVariationPricesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public float $rate
    ) {}

    public function handle(): void
    {
        VariationPriceService::updatePrices(
            $this->rate
        );
    }
}
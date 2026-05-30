<?php

namespace App\Jobs;

use App\Models\Variation;
use App\Services\VariationPriceCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class RecalculateVariationPriceJob implements ShouldQueue , ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $variationId
    ) {}

    public function handle(VariationPriceCalculator $calculator): void
    {
        $variation = Variation::with(['attributes.option'])->find($this->variationId);

        if (! $variation) {
            return;
        }

        $final = $calculator->calculateSellPrice($variation);

        $variation->update([
            'cached_final_price' => $final,
            'cached_profit' => $calculator->calculateProfit($variation, $final),
            'cached_profit_percentage' => $calculator->calculateProfitPercentage($variation, $final),
        ]);
    }
}
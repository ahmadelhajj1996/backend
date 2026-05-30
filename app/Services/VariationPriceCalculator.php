<?php

namespace App\Services;

use App\Models\Variation;
use App\Models\VariationAttribute;

class VariationPriceCalculator
{
    /**
     * Calculate final sell price
     */
    public function calculateSellPrice(Variation $variation): float
    {
        $attributes = $variation->relationLoaded('attributes')
            ? $variation->getRelation('attributes')
            : $variation->attributes()->with('option')->get();

        // 1. override wins
        $override = $attributes->firstWhere('is_price_override', true);

        if ($override && $override->price_override !== null) {
            return round((float) $override->price_override, 2);
        }

        // 2. base + modifiers
        $modifier = $attributes->sum(fn ($attr) =>
            (float) ($attr->option?->price_modifier ?? 0)
        );

        return round((float) $variation->sell_price + $modifier, 2);
    }

    /**
     * Calculate profit
     */
    public function calculateProfit(Variation $variation, float $finalPrice): float
    {
        return round($finalPrice - (float) $variation->buy_price, 2);
    }

    /**
     * Calculate profit percentage
     */
    public function calculateProfitPercentage(Variation $variation, float $finalPrice): float
    {
        if ((float) $variation->buy_price <= 0) {
            return 0;
        }

        return round(
            (($finalPrice - (float) $variation->buy_price) / (float) $variation->buy_price) * 100,
            2
        );
    }
}
<?php
namespace App\Services;

use App\Models\Variation;
use App\Services\CurrencyService;

class VariationPriceService
{

    public static function updateSellPrices(): void
    {
        $rate = CurrencyService::getRate();

        Variation::query()
            ->whereNotNull('base_price')
            ->chunkById(500, function ($variations) use ($rate) {

                foreach ($variations as $variation) {

                    $variation->forceFill([
                        'sell_price' => round($variation->base_price * $rate, 2),
                        'sell_rate'  => $rate,
                    ])->saveQuietly();
                }
            });
    }

    public static function updateBuyPrices(): void
    {
        $rate = CurrencyService::getRate();

        Variation::query()
            ->whereNotNull('base_buy_price')
            ->chunkById(500, function ($variations) use ($rate) {

                foreach ($variations as $variation) {
                    $variation->forceFill([
                        'buy_price' => round($variation->base_buy_price * $rate, 2),
                        'buy_rate'  => $rate,
                    ])->saveQuietly();
                }
            });
    }
}

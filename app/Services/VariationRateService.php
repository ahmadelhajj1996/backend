<?php
namespace App\Services;

use App\Services\CurrencyService;

class VariationRateService
{
    public static function snapshot(): array
    {
        $rate = CurrencyService::getRate();

        return [
            'sell_rate' => $rate,
            'buy_rate'  => $rate,
        ];
    }
}
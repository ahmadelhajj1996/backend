<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Cache;

class CurrencyService
{
    /**
     * Latest USD/SYP rate
     */
    public static function usdRate(): ?float
    {
        return Cache::remember(
            'latest_usd_syp_rate',
            now()->addMinutes(10),
            fn () => ExchangeRate::latest('created_at')
                ->value('rate')
        );
    }

    /**
     * Convert SYP to USD
     */
    public static function sypToUsd(
        float $amount
    ): ?float {

        $rate = self::usdRate();

        if (! $rate || $rate <= 0) {
            return null;
        }

        return round($amount / $rate, 2);
    }

    /**
     * Convert USD to SYP
     */
    public static function usdToSyp(
        float $amount
    ): ?float {

        $rate = self::usdRate();

        if (! $rate || $rate <= 0) {
            return null;
        }

        return round($amount * $rate, 2);
    }
}
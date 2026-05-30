<?php
namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Cache;

class CurrencyService
{

    protected const CACHE_KEY = 'latest_usd_syp_rate';
    protected const CACHE_MINUTES = 10;

    public static function getRate(): float
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(self::CACHE_MINUTES),
            fn () => (float) ExchangeRate::query()
                ->latest('created_at')
                ->value('rate')
        ) ?: 1;
    }

    public static function clearRateCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
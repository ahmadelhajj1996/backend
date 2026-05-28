<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class VariationPriceService
{
    /**
     * Recalculate all SYP prices
     * using old and new exchange rates
     */
    public static function updatePrices(
        float $newRate
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Get previous exchange rate
        |--------------------------------------------------------------------------
        */

        $oldRate = DB::table('exchange_rates')
            ->latest('id')
            ->skip(1)
            ->value('rate');

        /*
        |--------------------------------------------------------------------------
        | Safety check
        |--------------------------------------------------------------------------
        */

        if (! $oldRate || $oldRate <= 0) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Formula
        |--------------------------------------------------------------------------
        |
        | old_price_syp / old_rate = usd
        | usd * new_rate = new_syp
        |
        | simplified:
        |
        | new_price =
        | (price / old_rate) * new_rate
        |
        |--------------------------------------------------------------------------
        */

        DB::statement("
            UPDATE variations
            SET price = ROUND(
                (price / ?) * ?,
                2
            )
        ", [
            $oldRate,
            $newRate,
        ]);
    }
}
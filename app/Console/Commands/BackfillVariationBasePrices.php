<?php

namespace App\Console\Commands;

use App\Models\Variation;
use Illuminate\Console\Command;

class BackfillVariationBasePrices extends Command
{
    protected $signature = 'variations:backfill-base-prices';

    protected $description = 'Backfill variation base prices';

    public function handle(): void
    {
        Variation::query()
            ->whereNull('base_price')
            ->whereNotNull('sell_rate')
            ->chunkById(100, function ($variations) {

                foreach ($variations as $variation) {

                    if ((float) $variation->sell_rate <= 0) {
                        continue;
                    }

                    $basePrice = (float) $variation->sell_price / (float) $variation->sell_rate;

                    $variation->update([
                        'base_price' => round($basePrice, 4),
                    ]);
                }
            });

        $this->info('Done.');
    }
}
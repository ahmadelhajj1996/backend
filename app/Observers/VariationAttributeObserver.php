<?php

namespace App\Observers;

use App\Models\VariationAttribute;
use App\Jobs\RecalculateVariationPriceJob;

class VariationAttributeObserver
{
    public function saved(VariationAttribute $attribute): void
    {
        RecalculateVariationPriceJob::dispatch($attribute->variation_id)
            ->delay(now()->addSeconds(2));
    }

    public function deleted(VariationAttribute $attribute): void
    {
        RecalculateVariationPriceJob::dispatch($attribute->variation_id)
            ->delay(now()->addSeconds(2));
    }
}
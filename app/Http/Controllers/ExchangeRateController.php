<?php

namespace App\Http\Controllers;

use App\Jobs\UpdateVariationPricesJob;
use App\Models\ExchangeRate;

class ExchangeRateController extends Controller
{
 

    public function index()
    {
        $rates = ExchangeRate::query()
            ->select([
                'id',
                'rate',
                'previous_rate',
                'change_amount',
                'change_percentage',
                'created_at',
            ])
            ->latest('id')
            ->take(2)
            ->get();

        return $this->successResponse(
            $rates,
            'Exchange rates retrieved successfully'
        );
    }
 
    public function updatePrices()
    {
        $latest = ExchangeRate::latest('id')->first();

        if (! $latest) {

            return $this->errorResponse(
                'No exchange rate found',
                404
            );
        }

        UpdateVariationPricesJob::dispatch(
            $latest->rate
        );

        return $this->successResponse(
            null,
            'Variation prices update started successfully'
        );
    }
}
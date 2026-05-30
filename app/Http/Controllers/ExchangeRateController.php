<?php
namespace App\Http\Controllers;

use App\Jobs\UpdateVariationPricesJob;
use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Artisan;

class ExchangeRateController extends Controller
{
    public function index()
    {

        // Artisan::call('exchange:fetch');

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
            ->take(5)
            ->get();

        return $this->successResponse(
            $rates,
            'Exchange rates retrieved successfully'
        );
    }

    public function fetch()
    {

        $exitCode = Artisan::call('exchange:fetch');
        if ($exitCode !== 0) {
            return $this->errorResponse(
                'Failed to fetch exchange rates',
                500
            );
        }

        $latest = ExchangeRate::query()
            ->latest('id')
            ->first();

        return $this->successResponse(
            $latest,
            'Exchange rates fetched successfully'
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

        UpdateVariationPricesJob::dispatch();

        return $this->successResponse(
            null,
            'Variation prices update started successfully'
        );
    }
}

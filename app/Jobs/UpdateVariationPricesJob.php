<?php
namespace App\Jobs;

use App\Services\VariationPriceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateVariationPricesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function handle(): void
    {
        \Log::info("JOB STARTED");

        VariationPriceService::updateSellPrices();

        VariationPriceService::updateBuyPrices();

        \Log::info("JOB FINISHED");

    }
}

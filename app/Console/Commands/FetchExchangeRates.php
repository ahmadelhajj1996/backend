<?php
namespace App\Console\Commands;

use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Admin;
use App\Notifications\ExchangeRateChangedNotification;

class FetchExchangeRates extends Command
{
    protected $signature = 'exchange:fetch';

    protected $description = 'Fetch and store USD to SYP exchange rate';

    public function handle(
        ExchangeRateService $service
    ): int {

        /*
        Fetch latest rate
        */

        $data = $service->fetchUsdToSyp();

        /*
        Validate response
        */

        if (! $this->isValidRate($data)) {

            $this->error(
                'Invalid exchange rate response.'
            );

            Log::error(
                'Invalid exchange rate data',
                [
                    'data' => $data,
                ]
            );

            return self::FAILURE;
        }

        /*
        Extract new rate
        */

        $newRate = (float) $data['rate'];

        /*
        Get latest stored rate
        */

        $latest = ExchangeRate::query()
            ->where('base_currency', 'USD')
            ->where('target_currency', 'SYP')
            ->latest('id')
            ->first();

        $previousRate = $latest?->rate;

        /*
        Prevent duplicate saves
        */

        if (
            $previousRate !== null &&
            abs($previousRate - $newRate) < 1
        ) {

            $this->info(
                "No changes detected. Current rate: {$newRate}"
            );

            return self::SUCCESS;
        }

        /*
        Calculate changes
        */

        $changeAmount = null;

        $changePercentage = null;

        if ($previousRate !== null) {

            $changeAmount =
                $newRate - $previousRate;

            if ($previousRate > 0) {

                $changePercentage =
                    (
                    $changeAmount /
                    $previousRate
                ) * 100;
            }
        }

        $exchange = DB::transaction(
            function () use (
                $newRate,
                $previousRate,
                $changeAmount,
                $changePercentage
            ) {

                return ExchangeRate::create([
                    'base_currency'     => 'USD',

                    'target_currency'   => 'SYP',

                    'rate'              => $newRate,

                    'previous_rate'     => $previousRate,

                    'change_amount'     => $changeAmount,

                    'change_percentage' => $changePercentage,
                ]);
            }
        );

        /*
        Send notifications
        */

        $type = null;

        $absChange = abs(
            $changePercentage ?? 0
        );

        if ($absChange > 10) {

            $type = 'critical';

        } elseif ($absChange > 5) {

            $type = 'warning';

        } elseif ($absChange > 1) {

            $type = 'normal';
        }

        if ($type !== null) {

            $admins = Admin::all();

            foreach ($admins as $admin) {
                $admin->notify(
                    new ExchangeRateChangedNotification(
                        $exchange,
                        $type
                    )
                );
            }
        }



        $this->info(
            "Saved successfully: 1 USD = {$newRate} SYP"
        );

        if ($previousRate !== null) {

            $this->line(
                "Previous Rate: {$previousRate}"
            );

            $this->line(
                "Change Amount: {$changeAmount}"
            );

            $this->line(
                "Change Percentage: " .
                round($changePercentage, 2) .
                "%"
            );
        }

        return self::SUCCESS;
    }

    private function isValidRate(
        ?array $data
    ): bool {

        return
        $data &&
        isset($data['rate']) &&
        is_numeric($data['rate']) &&
        (float) $data['rate'] > 0;
    }
}

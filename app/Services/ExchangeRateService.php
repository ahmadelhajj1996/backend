<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private const URL =
        'https://www.sp-today.com/en/currency/us-dollar';

    public function fetchUsdToSyp(): ?array
    {
        try {

            $response = Http::timeout(20)
                ->retry(3, 2000)
                ->withHeaders([
                    'User-Agent' =>
                        'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',

                    'Accept' => 'text/html',

                    'Accept-Language' =>
                        'en-US,en;q=0.9',

                    'Cache-Control' => 'no-cache',
                ])
                ->withOptions([
                    'verify' => false,
                ])
                ->get(self::URL);

 
            Log::info('SP Today status', [
                'status' => $response->status(),
            ]);

            if (! $response->successful()) {

                Log::error(
                    'SP Today request failed',
                    [
                        'status' =>
                            $response->status(),
                    ]
                );

                return null;
            }

            $html = $response->body();

            /*
            Convert HTML to plain text
            */

            $text = strip_tags($html);

            /*
            Normalize spaces
            */

            $text = preg_replace(
                '/\s+/',
                ' ',
                $text
            );

            /*
            DEBUG
            */

            Log::info('Page snippet', [
                'snippet' =>
                    substr($text, 0, 1000),
            ]);

            /*
            Match:

            Buy13,825SYPSell13,925SYP
            */

            preg_match(
                '/Buy\s*([\d,]+)\s*SYP\s*Sell\s*([\d,]+)\s*SYP/i',
                $text,
                $matches
            );

            Log::info('Regex matches', [
                'matches' => $matches,
            ]);

            /*
            matches[1] => buy
            matches[2] => sell
            */

            if (! isset($matches[2])) {

                Log::error(
                    'USD sell rate not found'
                );

                return null;
            }

            /*
            Use SELL price
            */

            $sellRate = str_replace(
                ',',
                '',
                $matches[2]
            );

            $rate = (float) $sellRate;

            Log::info('Extracted rate', [
                'rate' => $rate,
            ]);

            if ($rate <= 0) {

                Log::error(
                    'Invalid extracted rate',
                    [
                        'rate' => $rate,
                    ]
                );

                return null;
            }

            return [
                'base'   => 'USD',

                'target' => 'SYP',

                'rate'   => $rate,

                'source' => 'sp-today',
            ];

        } catch (\Throwable $e) {

            Log::error(
                'Exchange fetch failed',
                [
                    'message' =>
                        $e->getMessage(),

                    'trace' =>
                        $e->getTraceAsString(),
                ]
            );

            return null;
        }
    }
}

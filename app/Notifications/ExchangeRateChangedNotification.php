<?php

namespace App\Notifications;

use App\Models\ExchangeRate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ExchangeRateChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public ExchangeRate $exchange;

    public string $type;

    public function __construct(
        ExchangeRate $exchange,
        string $type
    ) {
        $this->exchange = $exchange;

        $this->type = $type;
    }

    /**
     * Channels
     */
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Database Notification
     */
    public function toArray($notifiable): array
    {
        $rate = $this->exchange->rate;

        $previousRate = $this->exchange->previous_rate;

        $changeAmount = $this->exchange->change_amount;

        $changePercentage = round(
            $this->exchange->change_percentage ?? 0,
            2
        );

        if ($this->type === 'critical') {

            return [

                'title' => 'تحذير حرج بتغير سعر الصرف',

                'code' => 'critical_exchange_warning',

                'message' =>
                    "تحذير حرج بتغير سعر الصرف",

                'type' => 'critical',

                'rate' => $rate,

                'previous_rate' => $previousRate,

                'change_amount' => $changeAmount,

                'change_percentage' => $changePercentage,

                'time' => now()->format('m-d h:i a'),
            ];
        }


        if ($this->type === 'warning') {
            return [

                'title' => 'تحذير بتغير سعر الصرف',

                'code' => 'exchange_warning',

                'message' => 'تحذير بتغير سعر الصرف',
                'type' => 'warning',

                'rate' => $rate,

                'previous_rate' => $previousRate,

                'change_amount' => $changeAmount,

                'change_percentage' => $changePercentage,

                'time' => now()->format('m-d h:i a'),
            ];
        }


        return [

            'title' => 'تم تحديث سعر الصرف',

            'code' => 'exchange_change',

            'message' => 'تم تحديث سعر الصرف',

            'type' => 'normal',

            'rate' => $rate,

            'previous_rate' => $previousRate,

            'change_amount' => $changeAmount,

            'change_percentage' => $changePercentage,

            'time' => now()->format('m-d h:i a'),
        ];
    }

    /**
     * Broadcast Type
     */
    public function broadcastType(): string
    {
        return 'exchange-rate-changed';
    }

 
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage(
            $this->toArray($notifiable)
        );
    }
}
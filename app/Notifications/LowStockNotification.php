<?php
namespace App\Notifications;

use App\Models\Variation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $variation;

    public $type;

    public function __construct(Variation $variation, string $type)
    {
        $this->variation = $variation;

        $this->type = $type;
    }

    /**
     * Channels
     */
    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    /**
     * Database Notification
     */
    public function toArray($notifiable)
    {
        $productName = $this->variation->product?->name;

        $sku = $this->variation->sku;

        $quantity = $this->variation->quantity;

        $variationId = $this->variation->id;

        /*
        |--------------------------------------------------------------------------
        | Critical Warning
        |--------------------------------------------------------------------------
        */

        if ($this->type === 'critical') {

            return [

                'title'   => 'تحذير حرج بالمخزون',

                'code'    => 'critical_quantity_warning',

                'message' => "    الشكل :  {$variationId} للمنتج : {$productName}    ( أوشك على النفاذ، الكمية المتبقية فقط : {$quantity} )",

                'variation_id'       => $variationId,

                'product_id'         => $this->variation->product_id,

                'sku'                => $sku,

                'remaining_quantity' => $quantity,

                'image'              => $this->variation->image_url,

                'time'               => now()->format('m-d h:i a'),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Low Stock Warning
        |--------------------------------------------------------------------------
        */

        return [

            'title'   => 'تنبيه انخفاض المخزون',

            'code'    => 'quantity_warning',

            'message' => "          الشكل :  {$variationId} للمنتج : {$productName}    ( الكمية المتبقية   : {$quantity} )",

            'variation_id' => $variationId,

            'product_id' => $this->variation->product_id,

            'sku' => $sku,

            'remaining_quantity' => $quantity,

            'image' => $this->variation->image_url,

            'time' => now()->format('m-d h:i a'),
        ];
    }


    public function broadcastType()
    {
        return 'low-stock';
    }


    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage(
            $this->toArray($notifiable)
        );
    }
}

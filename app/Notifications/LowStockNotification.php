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
    public $selectedAttributes;

    public function __construct(Variation $variation, string $type, array $selectedAttributes = [])
    {
        $this->variation          = $variation;
        $this->type               = $type;
        $this->selectedAttributes = $selectedAttributes;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        $variation   = $this->variation;
        $productName = $variation->product?->name;
        $sku         = $variation->sku;
        $quantity    = $variation->quantity;
        $variationId = $variation->id;

        $attributeString = "#{$variationId}";

        if (!empty($this->selectedAttributes)) {

            $parts = [];

            foreach ($this->selectedAttributes as $attr) {
                if (isset($attr['name'], $attr['value'])) {
                    $parts[] = trim($attr['name']) . ': ' . trim($attr['value']);
                }
            }

            if (!empty($parts)) {
                $attributeString = implode(' - ', $parts);
            }

        } else {
            $readableAttributes = $variation->resolveReadableAttributes();

            if (!empty($readableAttributes) && is_array($readableAttributes)) {

                $formattedAttributes = [];

                foreach ($readableAttributes as $attr) {
                    if (isset($attr['attribute_name'], $attr['value'])) {
                        $name  = trim($attr['attribute_name']);
                        $value = trim($attr['value']);
                        $formattedAttributes[$name] = "{$name}: {$value}";
                    }
                }
                if (!empty($formattedAttributes)) {
                    $attributeString = implode(' - ', $formattedAttributes);
                }
            }
        }

        $baseData = [
            'variation_id'       => $variationId,
            'product_id'         => $variation->product_id,
            'sku'                => $sku,
            'remaining_quantity' => $quantity,
            'image'              => $variation->image_url,
            'attributes'         => $attributeString,
            'time'               => now()->format('m-d h:i a'),
        ];

        if ($this->type === 'critical') {
            return array_merge($baseData, [
                'title'   => 'تحذير حرج بالمخزون',
                'code'    => 'critical_quantity_warning',
                'message' => " {$attributeString} - المنتج: {$productName} (أوشك على النفاذ، الكمية المتبقية فقط: {$quantity})",
            ]);
        }

        return array_merge($baseData, [
            'title'   => 'تنبيه انخفاض المخزون',
            'code'    => 'quantity_warning',
            'message' => " {$attributeString} - المنتج: {$productName} (الكمية المتبقية: {$quantity})",
        ]);
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

<?php
namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {

        $id = $this->id;

        $name = $this->order->client?->name;

        $phone = $this->order->client?->phone;

        $order_id = $this->order->id;


        return [
            'title'   => 'طلب جديد',

            'code'    => 'new_order',

            'message' => "تم تسجيل طلب جديد رقم :  {$order_id} من السيد/السيدة  :  {$name}  رقم الهاتف :   {$phone}  بقيمة اجمالية : {$this->order->grand_total} ل.س",

            'order_id' => $order_id,

            'time' => now()->format('m-d      h:i a'),
        ];
    }

    public function broadcastType()
    {
        return 'new-order';
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}

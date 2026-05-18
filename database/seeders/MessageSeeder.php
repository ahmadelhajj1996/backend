<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Message;

class MessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $messages = [
            [
                'content' => "اكتشف أحدث صيحات الموضة!",
            ],
            [
                'content' => "تسوق مجموعات حصرية لكل المناسبات",
            ],
            [
                'content' => "جدد أناقتك مع مجموعتنا المختارة بعناية.",

            ],
            [
                'content' => "استمتع بشحن سريع وأسعار لا تُقارن.",

            ],
        ];

        foreach ($messages as $message) {
            Message::create([
                'content' => $message['content'],
            ]);
        }
    }
}

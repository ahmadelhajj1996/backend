<?php
namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {



        Broadcast::routes([
            'prefix'     => 'api/admin',
            'middleware' => ['auth:admin'],
        ]);
        
        // Broadcast::routes([
        //     'prefix'     => 'api/client',
        //     'middleware' => ['auth:client'],
        // ]);

        require base_path('routes/channels.php');
    }
}

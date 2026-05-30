<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\VariationAttribute;
use App\Observers\VariationAttributeObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VariationAttribute::observe(VariationAttributeObserver::class);

    }
}

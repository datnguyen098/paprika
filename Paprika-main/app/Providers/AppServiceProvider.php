<?php

namespace App\Providers;

use App\Models\Promotion;
use App\Support\PendingVivaPayment;
use App\Support\StorefrontNavigation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

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
        Schema::defaultStringLength(191);

        Blade::if('permission', fn (string ...$permissions): bool => auth()->user()?->hasAnyPermission($permissions) ?? false);

        View::composer(['layouts.app', 'storefront.layouts.app'], function ($view): void {
            $view->with([
                'globalPopupPromotion' => Promotion::current()
                    ->with('translations')
                    ->where('placement', 'popup')
                    ->orderBy('sort_order')
                    ->latest()
                    ->first(),
                'headerMenus' => StorefrontNavigation::forLocation('header'),
                'footerMenus' => StorefrontNavigation::forLocation('footer'),
                'pendingVivaPayment' => app(PendingVivaPayment::class)->reminder(),
            ]);
        });
    }
}

<?php

namespace App\Providers;

use App\Support\Currency;
use App\Support\Localization;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Support/helpers.php');

        $this->app->bind(\App\Services\LoyverseService::class, function ($app) {
            return new \App\Services\LoyverseService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::share('supportedCurrencies', Currency::all());
        View::share('supportedLocales', Localization::all());

        View::composer('*', function ($view) {
            $activeCurrency = app()->bound('activeCurrency')
                ? app('activeCurrency')
                : tap($this->resolveActiveCurrency(), fn ($currency) => app()->instance('activeCurrency', $currency));

            $view->with('activeCurrency', $activeCurrency);

            $activeLocale = app()->bound('activeLocaleMeta')
                ? app('activeLocaleMeta')
                : Localization::get(app()->getLocale());

            $view->with('activeLocale', $activeLocale);
            $view->with('supportedLocales', Localization::all());

            $brandName = __('common.app.name_short');
            $user = request()?->user();

            if ($user) {
                $user->loadMissing('entity.profileSettings');

                $entity = $user->entity;
                $brandName = $entity?->profileSettings?->restaurant_name
                    ?? $entity?->name
                    ?? $brandName;
            }

            $view->with('appBrandName', $brandName);
        });
    }

    /**
     * Resolve the currency for the current authenticated entity.
     *
     * @return array<string, mixed>
     */
    protected function resolveActiveCurrency(): array
    {
        $request = request();
        $user = $request?->user();

        if ($user && ! $user->relationLoaded('entity')) {
            $user->load('entity');
        }

        $entityCurrency = $user?->entity?->currency;

        return Currency::get($entityCurrency);
    }
}

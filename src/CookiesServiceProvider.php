<?php

namespace Whitecube\LaravelCookieConsent;

use Illuminate\Support\ServiceProvider;

abstract class CookiesServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // Queued instead of executed: definitions are replayed whenever the
        // active site changes, so each hostname gets its own cookie set.
        $this->app->make(CookiesDefinitions::class)->push(
            fn(CookiesRegistrar $cookies) => $this->registerCookies($cookies)
        );
    }

    /**
     * Define the cookies users should be aware of.
     */
    abstract protected function registerCookies(CookiesRegistrar $cookies): void;

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        //
    }
}
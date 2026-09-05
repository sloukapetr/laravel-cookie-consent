<?php

namespace App\Providers;

use Whitecube\LaravelCookieConsent\CookiesRegistrar;
use Whitecube\LaravelCookieConsent\CookiesServiceProvider as ServiceProvider;

class CookiesServiceProvider extends ServiceProvider
{
    /**
     * Define the cookies users should be aware of.
     *
     * Runs once per request, for the site matching the current hostname.
     */
    protected function registerCookies(CookiesRegistrar $cookies): void
    {
        // Register Laravel's base cookies under the "required" cookies section:
        $cookies->essentials()
            ->session()
            ->csrf();

        // Third-party services (Google Analytics, Meta Pixel, ...) are declared
        // per site in config/cookieconsent.php and registered automatically.

        // Register custom cookies under the pre-existing "optional" category:
        // $cookies->optional()
        //     ->name('darkmode_enabled')
        //     ->description('This cookie helps us remember your preferences regarding the interface\'s brightness.')
        //     ->duration(120)
        //     ->accepted(fn(Consent $consent, MyDarkmode $darkmode) => $consent->cookie(value: $darkmode->getDefaultValue()));

        // Register cookies for one site only:
        // $cookies->forSite('acme', function (CookiesRegistrar $cookies) {
        //     $cookies->optional()
        //         ->name('acme_layout')
        //         ->description('Remembers the preferred portal layout.')
        //         ->duration(60 * 24 * 30);
        // });
    }
}

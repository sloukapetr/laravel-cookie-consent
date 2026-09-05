<?php

namespace Whitecube\LaravelCookieConsent\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array all()
 * @method static \Whitecube\LaravelCookieConsent\Sites\Site|null get(string $key)
 * @method static \Whitecube\LaravelCookieConsent\Sites\Site|null forHost(string $host)
 * @method static \Whitecube\LaravelCookieConsent\Sites\Site|null current(?\Illuminate\Http\Request $request = null)
 * @method static \Whitecube\LaravelCookieConsent\Sites\Site|null use(\Whitecube\LaravelCookieConsent\Sites\Site|string|null $site)
 * @method static void forget()
 * @method static string|null key()
 * @method static bool is(string ...$keys)
 *
 * @see \Whitecube\LaravelCookieConsent\Sites\SiteResolver
 */
class Site extends Facade
{
    public static function getFacadeAccessor()
    {
        return \Whitecube\LaravelCookieConsent\Sites\SiteResolver::class;
    }
}

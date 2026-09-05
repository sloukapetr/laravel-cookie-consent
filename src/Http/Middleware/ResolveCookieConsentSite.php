<?php

namespace Whitecube\LaravelCookieConsent\Http\Middleware;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Whitecube\LaravelCookieConsent\CookiesManager;
use Whitecube\LaravelCookieConsent\CookiesRegistrar;
use Whitecube\LaravelCookieConsent\Facades\Cookies;
use Whitecube\LaravelCookieConsent\Sites\SiteResolver;

class ResolveCookieConsentSite
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(
        protected Container $app,
        protected SiteResolver $resolver,
    ) {}

    /**
     * Determine which site is handling the incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $previous = $this->resolver->key();

        $this->resolver->forget();
        $this->resolver->current($request);

        if ($previous !== $this->resolver->key()) {
            // Cookie definitions depend on the active site and must be rebuilt.
            $this->app->forgetInstance(CookiesRegistrar::class);
            $this->app->forgetInstance(CookiesManager::class);

            Cookies::clearResolvedInstance(CookiesManager::class);
        }

        return $next($request);
    }
}

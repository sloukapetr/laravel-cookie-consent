<?php

namespace Whitecube\LaravelCookieConsent;

use Illuminate\Support\Facades\Config;
use Whitecube\LaravelCookieConsent\Facades\Site;

class EssentialCookiesCategory extends CookiesCategory
{
    /**
     * Define the package's consent cookie
     */
    public function consent(): static
    {
        if(! ($site = Site::current())) {
            return $this;
        }

        return $this->cookie(function(Cookie $cookie) use ($site) {
            $cookie->name($site->cookieName())
                ->duration($site->cookieDuration())
                ->description(__('cookieConsent::cookies.defaults.consent'));
        });
    }

    /**
     * Define Laravel's session cookie.
     */
    public function session(): static
    {
        return $this->cookie(function(Cookie $cookie) {
            $cookie->name(Config::get('session.cookie'))
                ->duration(Config::get('session.lifetime'))
                ->description(__('cookieConsent::cookies.defaults.session'));
        });
    }

    /**
     * Define Laravel's XSRF-TOKEN cookie.
     */
    public function csrf(): static
    {
        return $this->cookie(function(Cookie $cookie) {
            $cookie->name('XSRF-TOKEN')
                ->duration(Config::get('session.lifetime'))
                ->description(__('cookieConsent::cookies.defaults.csrf'));
        });
    }
}

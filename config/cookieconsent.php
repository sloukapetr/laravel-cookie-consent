<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | The package registers one single set of consent endpoints, shared by every
    | site: generated URLs always follow the hostname of the current request,
    | so there is no domain to configure here.
    |
    | Keep the "web" middleware group: the consent cookie is then encrypted the
    | same way on the way out and on the way in.
    |
    */

    'routes' => [
        'prefix' => 'cookie-consent',
        'middleware' => ['web'],
    ],

    'assets' => [
        'styles' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sites
    |--------------------------------------------------------------------------
    |
    | Every website served by this application, keyed by an arbitrary
    | identifier and matched against the incoming request's hostname. Add as
    | many entries as you need; single-site applications simply keep the one
    | catch-all entry below.
    |
    | hosts             Literal hostnames ("example.test"), wildcards
    |                   ("*.example.com", "*" for anything) or references to
    |                   other config values prefixed with "@" (for instance
    |                   "@app.domains.example", so you can reuse the very same
    |                   values as your Route::domain() groups). Entries
    |                   resolving to null are ignored, which makes it safe to
    |                   reference environments that are not always configured.
    |                   The first matching site wins, so keep catch-all entries
    |                   last. When nothing matches, no site is activated and the
    |                   consent notice is not rendered.
    |
    | cookie.name       Name of the anonymized cookie storing the user's
    |                   choices. It is registered under "essentials"
    |                   automatically. When null, it is derived from the site
    |                   key ("my_site" becomes "my_site_cookie_consent").
    |                   Distinct names are what keeps consent given on one
    |                   hostname from leaking onto another.
    | cookie.duration   Lifetime in minutes.
    | cookie.domain     Activity domain. Prefix with "." to share the consent
    |                   across sub-domains (eg: ".mydomain.com").
    |
    | policy            Route name or absolute URL of the cookie policy page.
    |
    | services          Third-party services enabled on this site. Their cookies
    |                   are declared automatically and their scripts are only
    |                   ever injected once the user consented to the matching
    |                   category. Services without an "id" are skipped, so one
    |                   is disabled by leaving its ID empty.
    |
    |                   Available out of the box: "google_analytics",
    |                   "meta_pixel", "hotjar" and "sklik". Register your own
    |                   from any service provider with:
    |                   Cookies::extendService('name', fn($cookies, $config) => …)
    |
    |                   Sklik accepts an extra "cookies" map (name => minutes)
    |                   since Seznam does not publish a stable cookie list.
    |
    |--------------------------------------------------------------------------
    |
    | Example, several websites in one application:
    |
    | 'sites' => [
    |     'acme' => [
    |         'hosts' => ['@app.domains.acme', 'acme.test', '*.acme.com'],
    |         'cookie' => ['name' => null, 'duration' => (60 * 24 * 365), 'domain' => null],
    |         'policy' => 'acme.cookies',
    |         'services' => [
    |             'google_analytics' => ['id' => env('GOOGLE_ANALYTICS_ID_ACME'), 'anonymize_ip' => true],
    |         ],
    |     ],
    |     'globex' => [
    |         'hosts' => ['@app.domains.globex', 'globex.test', '*.globex.org'],
    |         'cookie' => ['name' => null, 'duration' => (60 * 24 * 180), 'domain' => '.globex.org'],
    |         'policy' => 'globex.cookies',
    |         'services' => [
    |             'meta_pixel' => ['id' => env('META_PIXEL_ID_GLOBEX')],
    |         ],
    |     ],
    | ],
    |
    */

    'sites' => [

        'default' => [

            'hosts' => ['*'],

            'cookie' => [
                'name' => Str::slug(env('APP_NAME', 'laravel'), '_').'_cookie_consent',
                'duration' => (60 * 24 * 365),
                'domain' => null,
            ],

            'policy' => null,

            'services' => [
                'google_analytics' => [
                    'id' => env('GOOGLE_ANALYTICS_ID'),
                    'anonymize_ip' => env('GOOGLE_ANALYTICS_ANONYMIZE_IP', true),
                ],
            ],

        ],

    ],

];
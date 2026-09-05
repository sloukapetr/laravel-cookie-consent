<?php

namespace Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Whitecube\LaravelCookieConsent\Http\Middleware\ResolveCookieConsentSite;

class OrchestraTestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            \Whitecube\LaravelCookieConsent\ServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.domains.alpha', 'www.alpha.example');

        // Skipping the "web" group keeps CSRF and cookie encryption out of the way.
        $app['config']->set('cookieconsent.routes.middleware', [ResolveCookieConsentSite::class]);

        $app['config']->set('cookieconsent.sites', [
            'alpha' => [
                'hosts' => ['@app.domains.alpha', 'alpha.test', '*.dev.alpha.example'],
                'policy' => 'https://www.alpha.example/cookies',
                'services' => [
                    'google_analytics' => ['id' => 'G-ALPHA111'],
                ],
            ],
            'beta_site' => [
                'hosts' => ['beta.test', 'www.beta.example'],
                'cookie' => ['name' => 'custom_beta_consent', 'duration' => 60],
                'services' => [
                    'meta_pixel' => ['id' => '1234567890'],
                ],
            ],
            'plain' => [
                'hosts' => ['plain.test'],
            ],
        ]);
    }
}
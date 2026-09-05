<?php

use Whitecube\LaravelCookieConsent\Consent;
use Whitecube\LaravelCookieConsent\CookiesDefinitions;
use Whitecube\LaravelCookieConsent\CookiesManager;
use Whitecube\LaravelCookieConsent\CookiesRegistrar;
use Whitecube\LaravelCookieConsent\Sites\ServiceRegistry;
use Whitecube\LaravelCookieConsent\Sites\SiteResolver;

function registrarForSite(string $key): CookiesRegistrar
{
    app(SiteResolver::class)->forget();
    app(SiteResolver::class)->use($key);

    app()->forgetInstance(CookiesRegistrar::class);
    app()->forgetInstance(CookiesManager::class);

    return app(CookiesRegistrar::class);
}

it('only runs definitions scoped to the active site', function () {
    app(CookiesDefinitions::class)->push(fn(CookiesRegistrar $cookies) => $cookies
        ->forSite('alpha', fn(CookiesRegistrar $cookies) => $cookies->optional()
            ->name('alpha_only')
            ->duration(120)
        )
    );

    expect(registrarForSite('alpha')->hasCategory('optional'))->toBeTrue()
        ->and(registrarForSite('beta_site')->hasCategory('optional'))->toBeFalse();
});

it('accepts several site keys at once', function () {
    app(CookiesDefinitions::class)->push(fn(CookiesRegistrar $cookies) => $cookies
        ->forSite(['alpha', 'beta_site'], fn(CookiesRegistrar $cookies) => $cookies->optional()
            ->name('shared')
            ->duration(120)
        )
    );

    expect(registrarForSite('alpha')->hasCategory('optional'))->toBeTrue()
        ->and(registrarForSite('beta_site')->hasCategory('optional'))->toBeTrue();
});

it('registers custom third-party services from the site configuration', function () {
    app(ServiceRegistry::class)->extend('hotjar', fn(CookiesRegistrar $cookies, array $config) => $cookies
        ->analytics()
        ->name('_hjSession')
        ->duration(30)
        ->accepted(fn(Consent $consent) => $consent->script('<script>hotjar:' . $config['id'] . '</script>'))
    );

    config()->set('cookieconsent.sites.alpha.services', ['hotjar' => ['id' => 'HJ-1']]);

    $names = collect(registrarForSite('alpha')->getCategories())
        ->flatMap(fn($category) => array_map(fn($cookie) => $cookie->name, $category->getCookies()))
        ->all();

    expect($names)->toContain('_hjSession');
});

it('skips services without an identifier', function () {
    config()->set('cookieconsent.sites.alpha.services', ['google_analytics' => ['id' => null]]);

    expect(registrarForSite('alpha')->hasCategory('analytics'))->toBeFalse();
});

it('rejects unknown services', function () {
    config()->set('cookieconsent.sites.alpha.services', ['unicorn' => ['id' => 'x']]);

    registrarForSite('alpha');
})->throws(InvalidArgumentException::class, 'Unknown cookie consent service "unicorn"');

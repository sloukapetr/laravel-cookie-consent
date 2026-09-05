<?php

use Whitecube\LaravelCookieConsent\Sites\SiteResolver;

it('resolves every configured alias to the right site', function (string $host, string $key) {
    expect(app(SiteResolver::class)->forHost($host)?->key)->toBe($key);
})->with([
    ['alpha.test', 'alpha'],
    ['www.alpha.example', 'alpha'],
    ['staging.dev.alpha.example', 'alpha'],
    ['beta.test', 'beta_site'],
    ['WWW.BETA.example', 'beta_site'],
]);

it('returns nothing when no site serves the hostname', function () {
    expect(app(SiteResolver::class)->forHost('unknown.example'))->toBeNull();
});

it('lets the first matching site win', function () {
    config()->set('cookieconsent.sites', [
        'specific' => ['hosts' => ['shop.acme.com']],
        'catch_all' => ['hosts' => ['*']],
    ]);

    expect(app(SiteResolver::class)->forHost('shop.acme.com')?->key)->toBe('specific')
        ->and(app(SiteResolver::class)->forHost('acme.com')?->key)->toBe('catch_all');
});

it('memoizes the current site until forgotten', function () {
    $resolver = app(SiteResolver::class);

    $resolver->use('alpha');
    expect($resolver->key())->toBe('alpha');

    $resolver->forget();
    $resolver->use('beta_site');
    expect($resolver->key())->toBe('beta_site');
});

it('tells which site is handling the request', function () {
    app(SiteResolver::class)->use('alpha');

    expect(app(SiteResolver::class)->is('alpha'))->toBeTrue()
        ->and(app(SiteResolver::class)->is('beta_site'))->toBeFalse()
        ->and(app(SiteResolver::class)->is('alpha', 'beta_site'))->toBeTrue();
});

it('scales to an arbitrary number of sites', function () {
    config()->set('cookieconsent.sites', collect(range(1, 25))
        ->mapWithKeys(fn($i) => ["site_$i" => ['hosts' => ["site$i.test"]]])
        ->all());

    $site = app(SiteResolver::class)->forHost('site17.test');

    expect($site?->key)->toBe('site_17')
        ->and($site->cookieName())->toBe('site_17_cookie_consent');
});

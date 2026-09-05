<?php

use Whitecube\LaravelCookieConsent\Sites\Site;

it('matches literal hostnames', function () {
    $site = new Site('acme', ['hosts' => ['acme.test', 'www.acme.com']]);

    expect($site->matches('acme.test'))->toBeTrue()
        ->and($site->matches('www.acme.com'))->toBeTrue()
        ->and($site->matches('globex.test'))->toBeFalse();
});

it('matches wildcard hostnames', function () {
    $site = new Site('acme', ['hosts' => ['*.acme.com']]);

    expect($site->matches('www.acme.com'))->toBeTrue()
        ->and($site->matches('www.dev.acme.com'))->toBeTrue()
        ->and($site->matches('acme.com'))->toBeFalse();
});

it('matches anything with a catch-all host', function () {
    $site = new Site('fallback', ['hosts' => ['*']]);

    expect($site->matches('whatever.example'))->toBeTrue();
});

it('is case insensitive', function () {
    $site = new Site('acme', ['hosts' => ['WWW.Acme.COM']]);

    expect($site->matches('www.acme.com'))->toBeTrue();
});

it('resolves hosts referencing other config values', function () {
    config()->set('app.domains.acme', 'www.acme.example');

    $site = new Site('acme', ['hosts' => ['@app.domains.acme']]);

    expect($site->hosts())->toBe(['www.acme.example'])
        ->and($site->matches('www.acme.example'))->toBeTrue();
});

it('ignores hosts resolving to nothing', function () {
    config()->set('app.domains.acme', null);

    $site = new Site('acme', ['hosts' => ['@app.domains.acme', '', 'acme.test']]);

    expect($site->hosts())->toBe(['acme.test'])
        ->and($site->matches('anything.example'))->toBeFalse();
});

it('accepts a single host as a string', function () {
    $site = new Site('acme', ['hosts' => 'acme.test']);

    expect($site->matches('acme.test'))->toBeTrue();
});

it('derives the consent cookie name from the site key', function () {
    expect((new Site('my_site'))->cookieName())->toBe('my_site_cookie_consent')
        ->and((new Site('My Site'))->cookieName())->toBe('my_site_cookie_consent');
});

it('keeps an explicitly configured cookie name', function () {
    $site = new Site('acme', ['cookie' => ['name' => 'custom_consent']]);

    expect($site->cookieName())->toBe('custom_consent');
});

it('falls back to a default cookie duration', function () {
    expect((new Site('acme'))->cookieDuration())->toBe(Site::DEFAULT_COOKIE_DURATION)
        ->and((new Site('acme', ['cookie' => ['duration' => 60]]))->cookieDuration())->toBe(60);
});

it('exposes the cookie domain', function () {
    $site = new Site('acme', ['cookie' => ['domain' => '.acme.com']]);

    expect($site->cookieDomain())->toBe('.acme.com')
        ->and((new Site('acme'))->cookieDomain())->toBeNull();
});

it('resolves the policy as a route name or an absolute url', function () {
    Illuminate\Support\Facades\Route::get('cookies', fn() => '')->name('acme.cookies');
    app('router')->getRoutes()->refreshNameLookups();

    expect((new Site('acme', ['policy' => 'acme.cookies']))->policyUrl())->toEndWith('/cookies')
        ->and((new Site('acme', ['policy' => 'https://acme.com/cookies']))->policyUrl())->toBe('https://acme.com/cookies')
        ->and((new Site('acme'))->policyUrl())->toBeNull();
});

it('exposes arbitrary definition values', function () {
    $site = new Site('acme', ['services' => ['hotjar' => ['id' => 'abc']]]);

    expect($site->get('services.hotjar.id'))->toBe('abc')
        ->and($site->get('services.missing.id', 'fallback'))->toBe('fallback')
        ->and($site->services())->toBe(['hotjar' => ['id' => 'abc']]);
});

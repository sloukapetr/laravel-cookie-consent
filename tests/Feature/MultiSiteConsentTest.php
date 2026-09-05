<?php

use Whitecube\LaravelCookieConsent\CookiesManager;
use Whitecube\LaravelCookieConsent\CookiesRegistrar;
use Whitecube\LaravelCookieConsent\Sites\SiteResolver;

function consentCookie($response, string $name)
{
    return collect($response->headers->getCookies())
        ->first(fn($cookie) => $cookie->getName() === $name);
}

function onSite(string $key, Closure $callback)
{
    app(SiteResolver::class)->forget();
    app(SiteResolver::class)->use($key);

    app()->forgetInstance(CookiesRegistrar::class);
    app()->forgetInstance(CookiesManager::class);

    return $callback(app(CookiesManager::class));
}

it('stores a separate consent cookie for each hostname', function () {
    $alpha = $this->post('http://alpha.test/cookie-consent/accept-all');
    $beta = $this->post('http://beta.test/cookie-consent/accept-all');

    expect(consentCookie($alpha, 'alpha_cookie_consent'))->not->toBeNull()
        ->and(consentCookie($alpha, 'custom_beta_consent'))->toBeNull()
        ->and(consentCookie($beta, 'custom_beta_consent'))->not->toBeNull()
        ->and(consentCookie($beta, 'alpha_cookie_consent'))->toBeNull();
});

it('uses the site specific cookie lifetime', function () {
    $response = $this->post('http://beta.test/cookie-consent/accept-all');

    $cookie = consentCookie($response, 'custom_beta_consent');

    expect($cookie->getExpiresTime())->toBeLessThanOrEqual(time() + (60 * 60) + 5);
});

it('does not honour the consent given on another hostname', function () {
    $consent = json_encode(['consent_at' => time(), '_ga' => true]);

    $response = $this->withUnencryptedCookie('custom_beta_consent', $consent)
        ->post('http://alpha.test/cookie-consent/accept-all');

    expect(consentCookie($response, 'alpha_cookie_consent'))->not->toBeNull();
});

it('serves no consent notice on an unknown hostname', function () {
    onSite('alpha', function () {
        app(SiteResolver::class)->forget();
        app(SiteResolver::class)->use(null);

        app()->forgetInstance(CookiesRegistrar::class);
        app()->forgetInstance(CookiesManager::class);

        $manager = app(CookiesManager::class);

        expect($manager->site())->toBeNull()
            ->and($manager->shouldDisplayNotice())->toBeFalse()
            ->and($manager->renderView())->toBe('')
            ->and($manager->renderScripts())->toBe('');
    });
});

it('registers the analytics id of the current site only', function () {
    $scripts = onSite('alpha', fn(CookiesManager $cookies) => $cookies->accept('*')->getResponseScripts());

    expect(implode('', $scripts))->toContain('G-ALPHA111')
        ->and(implode('', $scripts))->not->toContain('1234567890');
});

it('registers the marketing service of the current site only', function () {
    $scripts = onSite('beta_site', fn(CookiesManager $cookies) => $cookies->accept('*')->getResponseScripts());

    expect(implode('', $scripts))->toContain('fbq(\'init\',\'1234567890\')')
        ->and(implode('', $scripts))->not->toContain('G-ALPHA111');
});

it('declares the cookies of the current site only', function () {
    $names = onSite('alpha', fn() => collect(app(CookiesRegistrar::class)->getCategories())
        ->flatMap(fn($category) => array_map(fn($cookie) => $cookie->name, $category->getCookies()))
        ->all());

    expect($names)->toContain('alpha_cookie_consent')
        ->and($names)->toContain('_ga_ALPHA111')
        ->and($names)->not->toContain('_fbp')
        ->and($names)->not->toContain('custom_beta_consent');
});

it('resets the consent cookie of the current site', function () {
    $response = $this->post('http://beta.test/cookie-consent/reset', [], ['HTTP_REFERER' => 'http://beta.test']);

    $cookie = consentCookie($response, 'custom_beta_consent');

    expect($cookie)->not->toBeNull()
        ->and($cookie->getValue())->toBeEmpty();
});

it('opens current consent settings without resetting the consent cookie', function () {
    $accepted = $this->post('http://beta.test/cookie-consent/accept-all');
    $consent = consentCookie($accepted, 'custom_beta_consent');

    $response = $this->withUnencryptedCookie('custom_beta_consent', $consent->getValue())
        ->withHeaders(['Accept' => 'application/json'])
        ->post('http://beta.test/cookie-consent/settings');

    expect($response->status())->toBe(200)
        ->and($response->json())->toHaveKeys(['status', 'scripts', 'notice'])
        ->and($response->json('notice'))
        ->toContain('value="marketing" id="cookies-policy-check-marketing" checked')
        ->and($response->headers->getCookies())->toBeEmpty();
});

<?php

use Whitecube\LaravelCookieConsent\CookiesManager;
use Whitecube\LaravelCookieConsent\CookiesRegistrar;
use Whitecube\LaravelCookieConsent\Sites\SiteResolver;

function managerForSite(string $key): CookiesManager
{
    app(SiteResolver::class)->forget();
    app(SiteResolver::class)->use($key);

    app()->forgetInstance(CookiesRegistrar::class);
    app()->forgetInstance(CookiesManager::class);

    return app(CookiesManager::class);
}

beforeEach(function () {
    config()->set('cookieconsent.sites.alpha.services', [
        'sklik' => ['id' => '987654', 'cookies' => ['sid' => 43200]],
        'hotjar' => ['id' => '111222'],
    ]);
});

it('sends the sklik retargeting hit with consent 0 before any consent', function () {
    $output = managerForSite('alpha')->renderScripts(withDefault: false);

    expect($output)->toContain('https://c.seznam.cz/js/rc.js')
        ->and($output)->toContain('rtgId:987654,consent:0')
        ->and($output)->toContain('window.sznIVA.IS.updateIdentities({eid:null})')
        ->and($output)->not->toContain('consent:1');
});

it('sends the sklik retargeting hit with consent 1 once consent is granted', function () {
    $manager = managerForSite('alpha');
    $manager->accept('*');

    $output = $manager->renderScripts(withDefault: false);

    expect($output)->toContain('rtgId:987654,consent:1')
        ->and($output)->not->toContain('consent:0');
});

it('never stores cookies for a refused service', function () {
    $response = $this->post('http://alpha.test/cookie-consent/accept-essentials');

    $names = array_map(fn($cookie) => $cookie->getName(), $response->headers->getCookies());

    expect($names)->toContain('alpha_cookie_consent')
        ->and($names)->not->toContain('sid');
});

it('declares the configured sklik cookies', function () {
    $names = collect(managerForSite('alpha')->getCategories())
        ->flatMap(fn($category) => array_map(fn($cookie) => $cookie->name, $category->getCookies()))
        ->all();

    expect($names)->toContain('sid');
});

it('loads hotjar only after consent', function () {
    $manager = managerForSite('alpha');

    expect($manager->renderScripts(withDefault: false))->not->toContain('static.hotjar.com');

    $manager->accept('*');

    expect($manager->renderScripts(withDefault: false))->toContain('static.hotjar.com')
        ->and($manager->renderScripts(withDefault: false))->toContain('hjid:111222');
});

it('declares the hotjar cookies including the site id', function () {
    $names = collect(managerForSite('alpha')->getCategories())
        ->flatMap(fn($category) => array_map(fn($cookie) => $cookie->name, $category->getCookies()))
        ->all();

    expect($names)->toContain('_hjSessionUser_111222')
        ->and($names)->toContain('_hjSession_111222')
        ->and($names)->toContain('_hjFirstSeen');
});

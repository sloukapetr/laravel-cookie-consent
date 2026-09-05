<?php

use Whitecube\LaravelCookieConsent\CookiesRegistrar;
use Whitecube\LaravelCookieConsent\EssentialCookiesCategory;
use Whitecube\LaravelCookieConsent\Facades\Site;

it('provides the cookies registrar singleton', function() {
    Site::use('plain');

    app(CookiesRegistrar::class)->essentials()->csrf();

    expect($categories = app(CookiesRegistrar::class)->getCategories())->toHaveLength(1);
    expect($category = $categories[0])->toBeInstanceOf(EssentialCookiesCategory::class);
    expect($category->getCookies())->toHaveLength(2);
});

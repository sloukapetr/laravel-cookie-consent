<?php

namespace Whitecube\LaravelCookieConsent\Http\Controllers;

use Illuminate\Http\Request;
use Whitecube\LaravelCookieConsent\CookiesManager;

class SettingsController
{
    public function __invoke(Request $request, CookiesManager $cookies)
    {
        return response()->json([
            'status' => 'ok',
            'scripts' => $cookies->getNoticeScripts(true),
            'notice' => $cookies->getNoticeMarkup(),
        ]);
    }
}

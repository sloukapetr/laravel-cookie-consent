<?php

use Illuminate\Support\Facades\Route;
use Whitecube\LaravelCookieConsent\Http\Controllers\ResetController;
use Whitecube\LaravelCookieConsent\Http\Controllers\SettingsController;
use Whitecube\LaravelCookieConsent\Http\Controllers\ScriptController;
use Whitecube\LaravelCookieConsent\Http\Controllers\AcceptAllController;
use Whitecube\LaravelCookieConsent\Http\Controllers\ConfigureController;
use Whitecube\LaravelCookieConsent\Http\Controllers\AcceptEssentialsController;

Route::group([
    'as' => 'cookieconsent.',
    'prefix' => config('cookieconsent.routes.prefix'),
    'middleware' => config('cookieconsent.routes.middleware')
], function() {
    Route::get('script', ScriptController::class)
        ->name('script');

    Route::post('accept-all', AcceptAllController::class)
        ->name('accept.all');

    Route::post('accept-essentials', AcceptEssentialsController::class)
        ->name('accept.essentials');

    Route::post('configure', ConfigureController::class)
        ->name('accept.configuration');

    Route::post('reset', ResetController::class)
        ->name('reset');

    Route::post('settings', SettingsController::class)
        ->name('settings');
});

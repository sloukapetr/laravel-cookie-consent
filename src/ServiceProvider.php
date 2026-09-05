<?php

namespace Whitecube\LaravelCookieConsent;


use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider as Provider;
use Whitecube\LaravelCookieConsent\Http\Middleware\ResolveCookieConsentSite;
use Whitecube\LaravelCookieConsent\Sites\ServiceRegistry;
use Whitecube\LaravelCookieConsent\Sites\SiteResolver;

class ServiceProvider extends Provider
{
    /**
     * Register the application services.
     */
    public function register()
    {
        if (!defined('LCC_ROOT')) {
            define('LCC_ROOT', realpath(__DIR__ . '/..'));
        }
        
        $this->mergeConfigFrom(LCC_ROOT.'/config/cookieconsent.php', 'cookieconsent');

        $this->app->singleton(SiteResolver::class, fn($app) => new SiteResolver($app['config']));
        $this->app->singleton(ServiceRegistry::class);
        $this->app->singleton(CookiesDefinitions::class);

        $this->registerDefaultServices();

        $this->app->singleton(CookiesRegistrar::class, function ($app) {
            $site = $app->make(SiteResolver::class)->current();

            $registrar = new CookiesRegistrar();

            // Shared upfront so definitions may safely resolve the registrar again.
            $app->instance(CookiesRegistrar::class, $registrar);

            $registrar->essentials()->consent();

            $app->make(ServiceRegistry::class)->applyTo($registrar, $site);
            $app->make(CookiesDefinitions::class)->applyTo($registrar);

            return $registrar;
        });
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->registerPublishables();

        $this->loadViewsFrom(
            LCC_ROOT.'/resources/views', 'cookie-consent'
        );

        $this->loadTranslationsFrom(LCC_ROOT.'/resources/lang', 'cookieConsent');

        $this->registerBladeDirectives();
        $this->registerMiddleware();

        $this->loadRoutesFrom(LCC_ROOT.'/routes/web.php');
    }

    /**
     * Define the third-party services that can be enabled from a site's configuration.
     */
    protected function registerDefaultServices(): void
    {
        $this->app->make(ServiceRegistry::class)
            ->extend('google_analytics', fn(CookiesRegistrar $cookies, array $config) => $cookies
                ->analytics()
                ->google(
                    id: $config['id'],
                    anonymizeIp: (bool) ($config['anonymize_ip'] ?? true),
                )
            )
            ->extend('meta_pixel', fn(CookiesRegistrar $cookies, array $config) => $cookies
                ->marketing()
                ->meta(id: $config['id'])
            )
            ->extend('hotjar', fn(CookiesRegistrar $cookies, array $config) => $cookies
                ->analytics()
                ->hotjar(id: $config['id'])
            )
            ->extend('sklik', fn(CookiesRegistrar $cookies, array $config) => $cookies
                ->marketing()
                ->sklik(id: $config['id'], cookies: $config['cookies'] ?? [])
            );
    }

    /**
     * Define everything the application is allowed to publish and customize.
     */
    protected function registerPublishables(): void
    {
        $this->publishes([
            LCC_ROOT.'/config/cookieconsent.php' => config_path('cookieconsent.php'),
        ], ['cookieconsent', 'cookieconsent-config']);

        $this->publishes([
            LCC_ROOT.'/resources/views' => resource_path('views/vendor/cookie-consent'),
        ], ['cookieconsent', 'cookieconsent-views']);

        $this->publishes([
            realpath(LCC_ROOT.'/resources/lang') => $this->app->langPath('vendor/cookieConsent'),
        ], ['cookieconsent', 'cookieconsent-lang']);

        $this->publishes([
            LCC_ROOT.'/stubs/CookiesServiceProvider.php' => app_path('Providers/CookiesServiceProvider.php'),
        ], ['cookieconsent', 'cookieconsent-provider']);

        $this->publishes([
            LCC_ROOT.'/dist' => public_path('vendor/cookie-consent'),
        ], ['cookieconsent', 'cookieconsent-assets']);
    }

    /**
     * Make sure the site handling the request is resolved on every web request.
     */
    protected function registerMiddleware(): void
    {
        if (! $this->app->bound(Kernel::class)) {
            return;
        }

        $kernel = $this->app->make(Kernel::class);

        if (method_exists($kernel, 'prependMiddlewareToGroup')) {
            $kernel->prependMiddlewareToGroup('web', ResolveCookieConsentSite::class);
        }
    }

    /**
     * Define the cookie-consent blade directives.
     */
    protected function registerBladeDirectives()
    {
        Blade::directive('cookieconsentscripts', function (string $expression) {
            return '<?php echo ' . Facades\Cookies::class . '::renderScripts(' . $expression . '); ?>';
        });

        Blade::directive('cookieconsentview', function (string $expression) {
            return '<?php echo ' . Facades\Cookies::class . '::renderView(); ?>';
        });

        Blade::directive('cookieconsentbutton', function (string $expression) {
            return '<?php echo ' . Facades\Cookies::class . '::renderButton(' . $expression . '); ?>';
        });

        Blade::directive('cookieconsentinfo', function () {
            return '<?php echo ' . Facades\Cookies::class . '::renderInfo(); ?>';
        });
    }
}

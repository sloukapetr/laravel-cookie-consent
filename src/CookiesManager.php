<?php

namespace Whitecube\LaravelCookieConsent;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie as CookieFacade;
use Symfony\Component\HttpFoundation\Cookie as CookieComponent;
use Whitecube\LaravelCookieConsent\Sites\ServiceRegistry;
use Whitecube\LaravelCookieConsent\Sites\Site;
use Whitecube\LaravelCookieConsent\Sites\SiteResolver;

class CookiesManager
{
    /**
     * The cookies registrar.
     */
    protected CookiesRegistrar $registrar;

    /**
     * The site resolver.
     */
    protected SiteResolver $sites;

    /**
     * The current request.
     */
    protected Request $request;

    /**
     * The user's current consent preferences.
     */
    protected ?array $preferences = null;

    /**
     * Whether the consent preferences have been read yet.
     */
    protected bool $loaded = false;

    /**
     * Create a new Service Manager instance.
     */
    public function __construct(CookiesRegistrar $registrar, SiteResolver $sites, Request $request)
    {
        $this->registrar = $registrar;
        $this->sites = $sites;
        $this->request = $request;
    }

    /**
     * Retrieve the site handling the current request.
     */
    public function site(): ?Site
    {
        return $this->sites->current($this->request);
    }

    /**
     * Retrieve the user's consent preferences for the current site.
     */
    protected function preferences(): ?array
    {
        if (! $this->loaded) {
            $this->preferences = $this->getCurrentConsentSettings($this->request);
            $this->loaded = true;
        }

        return $this->preferences;
    }

    /**
     * Retrieve the eventual existing cookie data.
     */
    protected function getCurrentConsentSettings(Request $request): ?array
    {
        if (! ($site = $this->site())) {
            return null;
        }

        $preferences = ($raw = $request->cookie($site->cookieName()))
            ? json_decode($raw, true)
            : null;

        if(! $preferences || ! is_int($preferences['consent_at'] ?? null)) {
            return null;
        }

        // Check duration in case application settings have changed since the cookie was set.
        if($preferences['consent_at'] + ($site->cookieDuration() * 60) < time()) {
            return null;
        }

        return $preferences;
    }

    /**
     * Create fresh cookie data for the given consented categories.
     */
    protected function makeConsentSettings(array $categories): array
    {
        return array_reduce($this->registrar->getCategories(), function($values, $category) use ($categories) {
            $state = in_array($category->key(), $categories);
            return array_reduce($category->getCookies(), function($values, $cookie) use ($state) {
                $values[$cookie->name] = $state;
                return $values;
            }, $values);
        }, ['consent_at' => time()]);
    }

    /**
     * Register a custom third-party service driver.
     */
    public function extendService(string $name, \Closure $driver): static
    {
        app(ServiceRegistry::class)->extend($name, $driver);

        return $this;
    }

    /**
     * Transfer all undefined method calls to the registrar.
     */
    public function __call(string $method, array $arguments)
    {
        return $this->registrar->$method(...$arguments);
    }

    /**
     * Check if the current preference settings are sufficient. If not,
     * the cookie preferences notice should be displayed again.
     */
    public function shouldDisplayNotice(): bool
    {
        if(! $this->site()) {
            return false;
        }

        if(! $this->preferences()) {
            return true;
        }

        // Check if each defined cookie has been shown to the user yet.
        return array_reduce($this->registrar->getCategories(), function($state, $category) {
            return $state ? true : array_reduce($category->getCookies(), function(bool $state, Cookie $cookie) {
                return $state ? true : !array_key_exists($cookie->name, $this->preferences());
            }, false);
        }, false);
    }

    /**
     * Check if the user has given explicit consent for a specific cookie.
     */
    public function hasConsentFor(string $key): bool
    {
        if(! ($preferences = $this->preferences())) {
            return false;
        }

        $groups = array_reduce($this->registrar->getCategories(), function($results, $category) use ($key) {
            return array_reduce($category->getDefined(), function(array $results, Cookie|CookiesGroup $instance) use ($key) {
                if(is_a($instance, CookiesGroup::class) && $instance->name === $key) {
                    $results[] = $instance;
                }
                return $results;
            }, $results);
        }, []);

        $cookies = $groups
            ? array_unique(array_reduce($groups, fn($cookies, $group) => array_merge($cookies, array_map(fn($cookie) => $cookie->name, $group->getCookies())), []))
            : [$key];

        foreach($cookies as $cookie) {
            if(! boolval($preferences[$cookie] ?? false)) return false;
        }

        return true;
    }

    /**
     * Handle the incoming consent preferences accordingly.
     */
    public function accept(string|array $categories = '*'): ConsentResponse
    {
        if(! ($site = $this->site())) {
            return new ConsentResponse();
        }

        if(! is_array($categories) || ! $categories) {
            $categories = array_map(fn($category) => $category->key(), $this->registrar->getCategories());
        }

        $this->preferences = $this->makeConsentSettings($categories);
        $this->loaded = true;

        $response = $this->getConsentResponse();
        $response->attachCookie($this->makeConsentCookie($site));

        return $response;
    }

    /**
     * Call all the cookie callbacks matching the current consent state and
     * gather their scripts and/or cookies that should be returned along
     * the current request's response.
     */
    protected function getConsentResponse(): ConsentResponse
    {
        return array_reduce($this->registrar->getCategories(), function($response, $category) {
            return array_reduce($category->getDefined(), function(ConsentResponse $response, Cookie|CookiesGroup $instance) {
                return $this->hasConsentFor($instance->name)
                    ? $response->handleConsent($instance)
                    : $response->handleRefusal($instance);
            }, $response);
        }, new ConsentResponse());
    }

    /**
     * Create a new cookie instance for the given consented categories.
     */
    protected function makeConsentCookie(Site $site): CookieComponent
    {
        return CookieFacade::make(
            name: $site->cookieName(),
            value: json_encode($this->preferences),
            minutes: $site->cookieDuration(),
            domain: $site->cookieDomain(),
            secure: $this->request->isSecure()
        );
    }

    /**
     * Output all the scripts for current consent state.
     */
    public function renderScripts(bool $withDefault = true): string
    {
        if(! $this->site()) {
            return '';
        }

        $output = $this->getNoticeScripts($withDefault);

        // Refusal callbacks must run before consent, so scripts are always collected.
        foreach ($this->getConsentResponse()->getResponseScripts() ?? [] as $tag) {
            $output .= $tag;
        }

        if(strlen($output)) {
            $output = '<!-- Cookie Consent -->' . $output;
        }

        return $output;
    }

    public function getNoticeScripts(bool $withDefault): string
    {
        $output = $withDefault ? $this->getDefaultScriptTag() : '';

        $output .= '<script data-cookie-consent>'
            . file_get_contents(LCC_ROOT . '/dist/script.js')
            . '</script>';

        if (config('cookieconsent.assets.styles', true) === true) {
            $output .= '<style data-cookie-consent>'
                . file_get_contents(LCC_ROOT . '/dist/style.css')
                . '</style>';
        }

        return $output;
    }

    protected function getDefaultScriptTag(): string
    {
        return '<script '
            . 'src="' . route('cookieconsent.script') . '?id='
            . md5(\filemtime(LCC_ROOT . '/dist/script.js')) . '" '
            . 'defer'
            . '></script>';
    }

    /**
     * Output the consent alert/modal for current consent state.
     */
    public function renderView(): string
    {
        return $this->shouldDisplayNotice()
            ? $this->getNoticeMarkup()
            : '';
    }

    public function getNoticeMarkup(): string
    {
        return view('cookie-consent::cookies', [
            'cookies' => $this->registrar,
            'consent' => $this,
            'policy' => $this->site()?->policyUrl(),
            'site' => $this->site()?->key,
        ])->render();
    }

    /**
     * Output a single cookie consent action button.
     */
    public function renderButton(string $action, ?string $label = null, array $attributes = []): string
    {
        $url = match ($action) {
            'accept.all' => route('cookieconsent.accept.all'),
            'accept.essentials' => route('cookieconsent.accept.essentials'),
            'accept.configuration' => route('cookieconsent.accept.configuration'),
            'reset' => route('cookieconsent.reset'),
            'settings' => route('cookieconsent.settings'),
            default => null,
        };

        if(! $url) {
            throw new \InvalidArgumentException('Cookie consent action "' . $action . '" does not exist. Try one of these: "accept.all", "accept.essentials", "accept.configuration", "settings", "reset".');
        }

        $attributes = array_merge([
            'method' => 'post',
            'data-cookie-action' => $action,
        ], $attributes);

        if(! ($attributes['class'] ?? null)) {
            $attributes['class'] = 'cookiebtn';
        }

        $basename = explode(' ', $attributes['class'])[0];

        $attributes = collect($attributes)
            ->map(fn($value, $attribute) => $attribute . '="' . $value . '"')
            ->implode(' ');

        return view('cookie-consent::button', [
            'url' => $url,
            'label' => $label ?? $action, // TODO: use lang file
            'attributes' => $attributes,
            'basename' => $basename,
        ])->render();
    }

    /**
     * Output a table with all the cookies infos.
     */
    public function renderInfo(): string
    {
        return view('cookie-consent::info', [
            'cookies' => $this->registrar,
        ])->render();
    }

    public function replaceInfoTag(string $wysiwyg): string
    {
        $cookieConsentInfo = view('cookie-consent::info', [
            'cookies' => $this->registrar,
        ])->render();
        
        $formattedString = preg_replace(
            [
                '/\<(\w)[^\>]+\>\@cookieconsentinfo\<\/\1\>/',
                '/\@cookieconsentinfo/',
            ],
            $cookieConsentInfo,
            $wysiwyg,
        );

        return $formattedString;
    }
}

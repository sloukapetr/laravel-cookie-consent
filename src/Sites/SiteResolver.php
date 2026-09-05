<?php

namespace Whitecube\LaravelCookieConsent\Sites;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;

class SiteResolver
{
    /**
     * The site handling the current request.
     */
    protected ?Site $current = null;

    /**
     * Whether the current site has been looked up yet.
     */
    protected bool $resolved = false;

    /**
     * Create a new site resolver instance.
     */
    public function __construct(
        protected Repository $config,
    ) {}

    /**
     * Retrieve all the configured sites.
     *
     * @return array<string,Site>
     */
    public function all(): array
    {
        $sites = [];

        foreach ((array) $this->config->get('cookieconsent.sites', []) as $key => $definition) {
            $sites[$key] = new Site((string) $key, (array) $definition);
        }

        return $sites;
    }

    /**
     * Retrieve a single configured site.
     */
    public function get(string $key): ?Site
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * Find the first site serving the given hostname.
     */
    public function forHost(string $host): ?Site
    {
        foreach ($this->all() as $site) {
            if ($site->matches($host)) {
                return $site;
            }
        }

        return null;
    }

    /**
     * Retrieve the site handling the current request.
     */
    public function current(?Request $request = null): ?Site
    {
        if ($this->resolved) {
            return $this->current;
        }

        $request ??= app()->bound('request') ? app('request') : null;

        return $this->use($request ? $this->forHost($request->getHost()) : null);
    }

    /**
     * Activate the given site for the remainder of the request.
     */
    public function use(Site|string|null $site): ?Site
    {
        $this->current = is_string($site) ? $this->get($site) : $site;
        $this->resolved = true;

        return $this->current;
    }

    /**
     * Forget the previously resolved site.
     */
    public function forget(): void
    {
        $this->current = null;
        $this->resolved = false;
    }

    /**
     * Retrieve the current site's key.
     */
    public function key(): ?string
    {
        return $this->current()?->key;
    }

    /**
     * Check whether the current request is handled by one of the given sites.
     */
    public function is(string ...$keys): bool
    {
        return in_array($this->key(), $keys, true);
    }
}

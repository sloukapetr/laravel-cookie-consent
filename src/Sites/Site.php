<?php

namespace Whitecube\LaravelCookieConsent\Sites;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;

class Site
{
    /**
     * The consent cookie's lifetime used when the site does not define one.
     */
    const DEFAULT_COOKIE_DURATION = 60 * 24 * 365;

    /**
     * Create a new site instance.
     */
    public function __construct(
        public readonly string $key,
        protected array $definition = [],
    ) {}

    /**
     * Retrieve the site's hostnames, resolving "@config.key" references
     * and discarding the entries that are not configured.
     */
    public function hosts(): array
    {
        $hosts = $this->definition['hosts'] ?? [];

        return collect(is_array($hosts) ? $hosts : [$hosts])
            ->map(fn($host) => (is_string($host) && str_starts_with($host, '@'))
                ? config(substr($host, 1))
                : $host)
            ->flatten()
            ->filter(fn($host) => is_string($host) && trim($host) !== '')
            ->map(fn($host) => strtolower(trim($host)))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Check whether the given hostname belongs to this site.
     */
    public function matches(string $host): bool
    {
        $host = strtolower(trim($host));

        foreach ($this->hosts() as $pattern) {
            if (Str::is($pattern, $host)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve the name of this site's consent cookie.
     */
    public function cookieName(): string
    {
        return $this->get('cookie.name') ?: Str::slug($this->key, '_') . '_cookie_consent';
    }

    /**
     * Retrieve this site's consent cookie lifetime, in minutes.
     */
    public function cookieDuration(): int
    {
        return (int) ($this->get('cookie.duration') ?: static::DEFAULT_COOKIE_DURATION);
    }

    /**
     * Retrieve this site's consent cookie activity domain.
     */
    public function cookieDomain(): ?string
    {
        return $this->get('cookie.domain');
    }

    /**
     * Retrieve this site's cookie policy route name or URL.
     */
    public function policy(): ?string
    {
        return $this->get('policy') ?: null;
    }

    /**
     * Resolve this site's cookie policy into a usable URL.
     */
    public function policyUrl(): ?string
    {
        if (! ($policy = $this->policy())) {
            return null;
        }

        return Route::has($policy) ? route($policy) : $policy;
    }

    /**
     * Retrieve the third-party services enabled on this site.
     */
    public function services(): array
    {
        return array_filter((array) $this->get('services', []), 'is_array');
    }

    /**
     * Retrieve any value from this site's definition using "dot" notation.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->definition, $key, $default);
    }

    /**
     * Retrieve the site's raw definition.
     */
    public function definition(): array
    {
        return $this->definition;
    }
}

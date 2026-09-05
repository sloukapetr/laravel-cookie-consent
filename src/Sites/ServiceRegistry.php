<?php

namespace Whitecube\LaravelCookieConsent\Sites;

use Closure;
use Whitecube\LaravelCookieConsent\CookiesRegistrar;

class ServiceRegistry
{
    /**
     * The registered service drivers.
     *
     * @var array<string,Closure>
     */
    protected array $drivers = [];

    /**
     * Register a third-party service driver.
     */
    public function extend(string $name, Closure $driver): static
    {
        $this->drivers[$name] = $driver;

        return $this;
    }

    /**
     * Check whether a driver has been registered under the given name.
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->drivers);
    }

    /**
     * Declare the cookies of every service enabled on the given site.
     */
    public function applyTo(CookiesRegistrar $registrar, ?Site $site): void
    {
        foreach ($site?->services() ?? [] as $name => $config) {
            if (! $this->has($name)) {
                throw new \InvalidArgumentException(sprintf(
                    'Unknown cookie consent service "%s" configured on site "%s". Available services: %s.',
                    $name, $site->key, implode(', ', array_keys($this->drivers)) ?: 'none'
                ));
            }

            // An empty ID disables the service without having to remove its configuration.
            if (! ($config['id'] ?? null)) {
                continue;
            }

            $this->drivers[$name]($registrar, $config);
        }
    }
}

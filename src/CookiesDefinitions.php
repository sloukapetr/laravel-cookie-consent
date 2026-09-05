<?php

namespace Whitecube\LaravelCookieConsent;

use Closure;

class CookiesDefinitions
{
    /**
     * The queued cookie definition callbacks.
     *
     * @var array<Closure>
     */
    protected array $callbacks = [];

    /**
     * Queue a set of cookie definitions.
     */
    public function push(Closure $callback): static
    {
        $this->callbacks[] = $callback;

        return $this;
    }

    /**
     * Run all the queued definitions against the given registrar.
     */
    public function applyTo(CookiesRegistrar $registrar): void
    {
        foreach ($this->callbacks as $callback) {
            $callback($registrar);
        }
    }
}

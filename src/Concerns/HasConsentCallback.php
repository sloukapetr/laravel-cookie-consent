<?php

namespace Whitecube\LaravelCookieConsent\Concerns;

use Closure;
use Illuminate\Support\Facades\App;
use Whitecube\LaravelCookieConsent\Consent;

trait HasConsentCallback
{
    /**
     * The callback that should be called when consent is given.
     */
    protected ?Closure $callback = null;

    /**
     * The callback that should be called as long as consent is not given.
     */
    protected ?Closure $refusalCallback = null;

    /**
     * Set the cookie's consent callback.
     */
    public function accepted(Closure $callback): static
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Set the cookie's refusal callback.
     */
    public function refused(Closure $callback): static
    {
        $this->refusalCallback = $callback;

        return $this;
    }

    /**
     * Check if there is a defined consent callback.
     */
    public function hasConsentCallback(): bool
    {
        return ! is_null($this->callback);
    }

    /**
     * Check if there is a defined refusal callback.
     */
    public function hasRefusalCallback(): bool
    {
        return ! is_null($this->refusalCallback);
    }

    /**
     * Check if there is a defined consent callback.
     */
    public function getConsentResult(): Consent
    {
        $consent = new Consent($this);

        App::call($this->callback, ['consent' => $consent]);

        return $consent;
    }

    /**
     * Resolve the refusal callback's result.
     */
    public function getRefusalResult(): Consent
    {
        $consent = new Consent($this);

        App::call($this->refusalCallback, ['consent' => $consent]);

        return $consent;
    }
}

<?php

namespace Whitecube\LaravelCookieConsent;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie as CookieFacade;
use Symfony\Component\HttpFoundation\Cookie as CookieComponent;
use Symfony\Component\HttpFoundation\Response;

class ConsentResponse
{
    protected array $cookies = [];

    protected array $scripts = [];

    protected ?string $notice = null;

    public function handleConsent(Cookie|CookiesGroup $instance): static
    {
        if (! $instance->hasConsentCallback()) {
            return $this;
        }

        $consent = $instance->getConsentResult();

        $this->attachCookies($consent->getCookies());
        $this->attachScripts($consent->getScripts());

        return $this;
    }

    public function handleRefusal(Cookie|CookiesGroup $instance): static
    {
        if (! $instance->hasRefusalCallback()) {
            return $this;
        }

        $this->attachScripts($instance->getRefusalResult()->getScripts());

        return $this;
    }

    public function attachCookies(array $cookies): static
    {
        foreach ($cookies as $cookie) {
            $this->attachCookie($cookie);
        }

        return $this;
    }

    public function attachCookie(CookieComponent $cookie): static
    {
        $this->cookies[] = $cookie;

        return $this;
    }

    public function attachScripts(array $tags): static
    {
        foreach ($tags as $tag) {
            $this->attachScript($tag);
        }

        return $this;
    }

    public function attachScript(string $tag): static
    {
        $this->scripts[] = $tag;

        return $this;
    }

    public function queueCookies(): static
    {
        foreach ($this->cookies as $cookie) {
            CookieFacade::queue($cookie);
        }

        return $this;
    }

    public function toResponse(Request $request): Response
    {
        $response = $request->expectsJson()
            ? response()->json($this->getResponseData())
            : redirect()->back();

        foreach ($this->cookies as $cookie) {
            $response->withCookie($cookie);
        }

        return $response;
    }

    public function getResponseData(): array
    {
        return array_filter([
            'status' => 'ok',
            'scripts' => $this->getResponseScripts(),
            'notice' => $this->getResponseNotice(),
        ]);
    }

    public function getResponseScripts(): ?array
    {
        return $this->scripts ?: null;
    }

    protected function getResponseNotice(): ?string
    {
        return $this->notice ?: null;
    }
}
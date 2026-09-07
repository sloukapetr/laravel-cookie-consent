<?php

namespace Whitecube\LaravelCookieConsent\Livewire;

use Livewire\Component;
use Whitecube\LaravelCookieConsent\CookiesManager;

class CookieConsent extends Component
{
    public bool $isOpen = false;

    public bool $isCustomizing = false;

    /** @var array<string, bool> */
    public array $categories = [];

    public function mount(CookiesManager $cookies): void
    {
        $this->isOpen = $cookies->shouldDisplayNotice();

        foreach ($cookies->getCategories() as $category) {
            $this->categories[$category->key()] = $category->key() === 'essentials'
                || collect($category->getCookies())
                    ->every(fn ($cookie): bool => $cookies->hasConsentFor($cookie->name));
        }
    }

    public function acceptAll(CookiesManager $cookies): void
    {
        $this->save(
            $cookies,
            array_map(
                fn ($category): string => $category->key(),
                $cookies->getCategories(),
            ),
        );
    }

    public function acceptEssentials(CookiesManager $cookies): void
    {
        $this->save($cookies, ['essentials']);
    }

    public function customize(): void
    {
        $this->isCustomizing = true;
    }

    public function openSettings(): void
    {
        $this->isOpen = true;
        $this->isCustomizing = true;
    }

    public function saveCustomSelection(CookiesManager $cookies): void
    {
        $selectedCategories = collect($this->categories)
            ->filter()
            ->keys()
            ->prepend('essentials')
            ->unique()
            ->values()
            ->all();

        $this->save($cookies, $selectedCategories);
    }

    /**
     * @param array<int, string> $categories
     */
    private function save(CookiesManager $cookies, array $categories): void
    {
        $response = $cookies->accept($categories)->queueCookies();

        foreach ($cookies->getCategories() as $category) {
            $this->categories[$category->key()] = in_array(
                $category->key(),
                $categories,
                true,
            );
        }

        $this->dispatch(
            'cookie-consent:accepted',
            scripts: $response->getResponseScripts() ?? [],
        );

        $this->isOpen = false;
        $this->isCustomizing = false;
    }
    public function render(): mixed
    {
        $cookies = app(CookiesManager::class);

        return view('cookie-consent::livewire.cookie-consent', [
            'cookies' => $cookies,
            'policy' => $cookies->site()?->policyUrl(),
        ]);
    }
}
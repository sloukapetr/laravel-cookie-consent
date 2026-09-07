<div
    x-data="{
        addConsentScripts(scripts) {
            for (const html of scripts) {
                const container = document.createElement('div');
                container.innerHTML = html;

                const source = container.querySelector('script');

                if (! source) {
                    continue;
                }

                if (source.src && document.querySelector(`script[src='${source.src}']`)) {
                    continue;
                }

                const script = document.createElement('script');
                script.textContent = source.textContent;

                for (const attribute of source.attributes) {
                    script.setAttribute(attribute.name, attribute.value);
                }

                script.dataset.cookieConsent = 'true';
                document.head.appendChild(script);
            }
        }
    }"
    x-on:cookie-consent:accepted.window="addConsentScripts($event.detail.scripts)"
    x-on:cookie-consent:open-settings.window="$wire.openSettings()"
>
    @if ($isOpen)
        <div
            class="fixed inset-0 z-100 flex items-end overflow-y-auto bg-black/60 p-3 backdrop-blur-sm sm:p-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="cookie-consent-title"
        >
            <section class="w-full bg-base-100 shadow-2xl">
                <div class="mx-auto max-w-6xl px-5 py-6 sm:px-8 sm:py-7">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-sm font-semibold text-primary">
                                @lang('cookieConsent::cookies.manage')
                            </p>

                            <h2
                                id="cookie-consent-title"
                                class="mt-1 text-2xl font-bold text-base-content"
                            >
                                @lang('cookieConsent::cookies.title')
                            </h2>

                            <p class="mt-3 text-sm leading-6 text-base-content/75">
                                @lang('cookieConsent::cookies.intro')
                            </p>

                            @if ($policy)
                                <p class="mt-2 text-sm leading-6 text-base-content/75">
                                    @lang('cookieConsent::cookies.link', ['url' => $policy])
                                </p>
                            @endif
                        </div>

                        <div class="grid shrink-0 gap-2 sm:grid-cols-2 lg:w-80">
                            <button
                                type="button"
                                class="btn btn-neutral min-h-12"
                                wire:click="acceptEssentials"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="acceptEssentials">
                                    @lang('cookieConsent::cookies.essentials')
                                </span>
                                <span
                                    class="loading loading-spinner loading-sm"
                                    wire:loading
                                    wire:target="acceptEssentials"
                                ></span>
                            </button>

                            <button
                                type="button"
                                class="btn btn-primary min-h-12"
                                wire:click="acceptAll"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="acceptAll">
                                    @lang('cookieConsent::cookies.all')
                                </span>
                                <span
                                    class="loading loading-spinner loading-sm"
                                    wire:loading
                                    wire:target="acceptAll"
                                ></span>
                            </button>
                        </div>
                    </div>

                    @if (! $isCustomizing)
                        <button
                            type="button"
                            class="btn btn-outline btn-sm mt-5"
                            wire:click="customize"
                        >
                            @lang('cookieConsent::cookies.customize')
                        </button>
                    @else
                        <div class="mt-7 border-t border-base-300 pt-5">
                            <h3 class="font-bold text-base-content mt-1">
                                @lang('cookieConsent::cookies.customize')
                            </h3>

                            <p class="mt-1 text-sm text-base-content/70">
                                @lang('cookieConsent::cookies.customize_intro')
                            </p>

                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                @foreach ($cookies->getCategories() as $category)
                                    <article class="border border-base-300 bg-base-200 p-4">
                                        <div class="flex items-start justify-between gap-5">
                                            <div>
                                                <h4 class="font-semibold text-base-content">
                                                    {{ $category->title }}
                                                </h4>

                                                @if ($category->description)
                                                    <p class="mt-1 text-sm leading-5 text-base-content/70">
                                                        {{ $category->description }}
                                                    </p>
                                                @endif
                                            </div>

                                            @if ($category->key() === 'essentials')
                                                <input
                                                    type="checkbox"
                                                    class="toggle toggle-primary shrink-0"
                                                    checked
                                                    disabled
                                                    aria-label="{{ $category->title }}"
                                                >
                                            @else
                                                <input
                                                    type="checkbox"
                                                    class="toggle toggle-primary shrink-0"
                                                    wire:model="categories.{{ $category->key() }}"
                                                    aria-label="{{ $category->title }}"
                                                >
                                            @endif
                                        </div>

                                        <details class="mt-4 border-t border-base-300 pt-3">
                                            <summary class="cursor-pointer text-sm font-medium text-base-content">
                                                @lang('cookieConsent::cookies.details.more')
                                            </summary>

                                            <ul class="mt-3 space-y-2 text-sm text-base-content/70">
                                                @foreach ($category->getCookies() as $cookie)
                                                    <li>
                                                        <span class="font-medium text-base-content">
                                                            {{ $cookie->name }}
                                                        </span>

                                                        @if ($cookie->description)
                                                            <span> - {{ $cookie->description }}</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    </article>
                                @endforeach
                            </div>

                            <button
                                type="button"
                                class="btn btn-primary mt-5 min-h-12"
                                wire:click="saveCustomSelection"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="saveCustomSelection">
                                    @lang('cookieConsent::cookies.save')
                                </span>
                                <span
                                    class="loading loading-spinner loading-sm"
                                    wire:loading
                                    wire:target="saveCustomSelection"
                                ></span>
                            </button>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    @endif
</div>
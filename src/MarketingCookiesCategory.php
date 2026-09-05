<?php

namespace Whitecube\LaravelCookieConsent;

class MarketingCookiesCategory extends CookiesCategory
{
    const META_PIXEL = 'meta_pixel';
    const SKLIK = 'sklik';

    /**
     * Define the Meta (Facebook) Pixel cookies all at once.
     */
    public function meta(string $id): static
    {
        $this->group(function (CookiesGroup $group) use ($id) {
            $group->name(static::META_PIXEL)
                ->cookie(fn(Cookie $cookie) => $cookie->name('_fbp')
                    ->duration(90 * 24 * 60)
                    ->description(__('cookieConsent::cookies.defaults._fbp'))
                )
                ->cookie(fn(Cookie $cookie) => $cookie->name('_fbc')
                    ->duration(90 * 24 * 60)
                    ->description(__('cookieConsent::cookies.defaults._fbc'))
                )
                ->accepted(fn(Consent $consent) => $consent
                    ->script(
                        '<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?'
                        . 'n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;'
                        . 'n.push=n;n.loaded=!0;n.version=\'2.0\';n.queue=[];t=b.createElement(e);t.async=!0;'
                        . 't.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}'
                        . '(window,document,\'script\',\'https://connect.facebook.net/en_US/fbevents.js\');'
                        . 'fbq(\'init\',\'' . $id . '\');fbq(\'track\',\'PageView\');</script>'
                    )
                );
        });

        return $this;
    }

    /**
     * Define the Seznam Sklik retargeting cookies all at once.
     *
     * Sklik requires a retargeting hit on every page view, carrying consent 0
     * before the user agreed and consent 1 afterwards.
     */
    public function sklik(string $id, array $cookies = []): static
    {
        $this->group(function (CookiesGroup $group) use ($id, $cookies) {
            $group->name(static::SKLIK);

            foreach ($cookies as $name => $duration) {
                $group->cookie(fn(Cookie $cookie) => $cookie->name($name)
                    ->duration($duration)
                    ->description(__('cookieConsent::cookies.defaults.sklik'))
                );
            }

            $group->refused(fn(Consent $consent) => $consent
                    ->script($this->getSklikLoaderTag())
                    ->script($this->getSklikRetargetingTag($id, 0))
                )
                ->accepted(fn(Consent $consent) => $consent
                    ->script($this->getSklikLoaderTag())
                    ->script($this->getSklikRetargetingTag($id, 1))
                );
        });

        return $this;
    }

    protected function getSklikLoaderTag(): string
    {
        return '<script type="text/javascript" src="https://c.seznam.cz/js/rc.js"></script>';
    }

    /**
     * Build the retargeting hit, waiting for the asynchronously loaded "sznIVA" object.
     */
    protected function getSklikRetargetingTag(string $id, int $consent): string
    {
        return '<script>(function(){var mto=10000,cto=0,f=100,idi=setInterval(function(){'
            . 'if(typeof window.sznIVA!==\'undefined\'){'
            . 'window.sznIVA.IS.updateIdentities({eid:null});'
            . 'window.rc.retargetingHit({rtgId:' . $id . ',consent:' . $consent . '});'
            . 'clearInterval(idi);}'
            . 'cto+=f;if(cto>=mto){clearInterval(idi);}},f);})();</script>';
    }
}

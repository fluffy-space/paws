<?php

namespace Pupils\Components\Services\Analytics;

use Viewi\DI\Singleton;

/**
 * Client-side conversion events, for whatever analytics script the host page loaded.
 *
 * Page *views* are already counted by the tracking script itself. This is for the funnel steps
 * that have no URL of their own — a signup completing, an inline checkout finishing, the first
 * link being created — which are invisible to page-view tracking because the address bar never
 * changes.
 *
 * Provider-agnostic by construction: it calls `window.pirsch(name)` when that function exists and
 * does nothing at all when it doesn't. So a build with no tracking script, a self-hosted install
 * that opted out, and the whole server side are all silently no-ops, and no caller needs a guard.
 *
 * Both methods are a single inline-JS block, which the transpiler emits verbatim into the JS
 * method and PHP treats as a bare string expression — i.e. they are browser-only by construction
 * and cost nothing during SSR.
 */
#[Singleton]
class AnalyticsService
{
    /**
     * Fire a conversion event.
     *
     * Retries briefly, because the tracking script is loaded `defer` while component hydration is
     * not — on a cold load the first event can easily reach here before `window.pirsch` exists,
     * and a dropped `signup_completed` is exactly the data point nobody notices is missing.
     */
    public function track(string $eventName): void
    {
        <<<'javascript'
        (function (name) {
            var tries = 0;
            var send = function () {
                if (typeof window.pirsch === 'function') {
                    window.pirsch(name);
                    return;
                }
                // ~5s of grace, then give up rather than leak a timer forever.
                if (++tries < 20) {
                    window.setTimeout(send, 250);
                }
            };
            send();
        })(eventName);
        javascript;
    }

    /**
     * Fire an event at most once per browser, keyed by `$onceKey`.
     *
     * For milestones that are only interesting the first time — "created their first link" stops
     * meaning anything on the fortieth. The flag lives in localStorage, so this is per browser and
     * not per account: clearing storage or switching device can fire it again. That is the right
     * trade for a funnel metric, and far cheaper than asking the server "was that the first?" on
     * every save.
     */
    public function trackOnce(string $eventName, string $onceKey): void
    {
        <<<'javascript'
        (function (name, key) {
            var storageKey = 'pa_once_' + key;
            try {
                if (window.localStorage.getItem(storageKey)) {
                    return;
                }
                window.localStorage.setItem(storageKey, '1');
            } catch (e) {
                // Private mode / storage disabled: fall through and just send it.
            }
            $this.track(name);
        })(eventName, onceKey);
        javascript;
    }
}

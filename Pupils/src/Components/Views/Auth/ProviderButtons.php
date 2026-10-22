<?php

namespace Pupils\Components\Views\Auth;

use Pupils\Components\Services\Analytics\AnalyticsService;
use Pupils\Components\Services\Localization\HasLocalization;
use Viewi\Components\BaseComponent;
use Viewi\Components\DOM\DomEvent;
use Viewi\Components\Http\HttpClient;

/**
 * "Continue with Google / Microsoft / ..." above the login and register forms.
 *
 * The list comes from the server (`/api/authorization/oauth/providers`): the providers that are
 * listed in config `auth.providers` AND have credentials. So there is one place to switch a
 * provider on, and a button is drawn only when it works. An app that enabled nothing gets an
 * empty list and this renders nothing.
 *
 * Each button is a plain GET form to our own endpoint, which then redirects to the provider.
 * Nothing from the provider is loaded on the page, so a visitor who never clicks is never seen
 * by it. A form and not a link, so the client router leaves the navigation alone and it works
 * before hydration.
 *
 * The label of a provider is the string `oauth.continue.<key>`; Paws ships google and microsoft.
 */
class ProviderButtons extends BaseComponent
{
    use HasLocalization;

    /** Same-site path to come back to after signing in ('' = the default landing). */
    public string $redirect = '';

    /**
     * Event prefix for a click ('signup' fires `signup_oauth_clicked`), '' = not tracked.
     * It is the only way to see a visitor who left on the provider's consent screen: the start
     * endpoint is a redirect, which page-view tracking never sees.
     */
    public string $trackAs = '';

    /** One entry per enabled provider: key, action (start URL), label (localization key). */
    public array $buttons = [];
    public bool $any = false;

    public function __construct(private HttpClient $http, private AnalyticsService $analytics) {}

    public function init()
    {
        $this->http->get('/api/authorization/oauth/providers')->then(function ($response) {
            $buttons = [];
            foreach ($response['providers'] as $key) {
                $buttons[] = [
                    'key' => $key,
                    'action' => '/api/authorization/oauth/' . $key . '/start',
                    'label' => 'oauth.continue.' . $key,
                ];
            }
            $this->buttons = $buttons;
            $this->any = count($buttons) > 0;
        }, function () {
            // No list, no buttons: the form below still works.
        });
    }

    /**
     * Holds the GET back until the event has gone, then submits it. form.submit() does not
     * raise another submit event, so this runs once. Before hydration the form simply submits,
     * untracked.
     */
    public function start(DomEvent $event)
    {
        if ($this->trackAs === '') {
            return;
        }
        $event->preventDefault();
        $form = $event->target;
        $this->analytics->trackAndWait($this->trackAs . '_oauth_clicked', function () use ($form) {
            $form->submit();
        });
    }
}

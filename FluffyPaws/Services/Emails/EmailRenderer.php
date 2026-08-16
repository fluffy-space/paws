<?php

namespace FluffyPaws\Services\Emails;

use SharedPaws\Models\Emails\IPlainTextEmail;
use Viewi\App;

/**
 * Renders an email component into both bodies a multipart/alternative message needs.
 *
 * Mailers call this instead of `$viewiApp->engine()->render(...)` directly, so that "what is the
 * text part of this email" has one answer for every email in the system rather than one per mailer
 * — which is how `confirm-email` and `reset-password` ended up sending their own subject line as
 * their text body.
 */
class EmailRenderer
{
    public function __construct(private App $viewiApp)
    {
    }

    /**
     * @param string $componentClass the email component, e.g. ActivateUserEmail::class
     * @param array $params constructor params for the component, exactly as passed to render()
     */
    public function render(string $componentClass, array $params = []): RenderedEmail
    {
        $engine = $this->viewiApp->engine();
        $html = $engine->render($componentClass, $params)->body;

        // The engine addresses components by short name — it strips the namespace itself in
        // render(), and resolve() expects the same form.
        $name = strpos($componentClass, '\\') !== false
            ? substr(strrchr($componentClass, '\\'), 1)
            : $componentClass;
        // Resolved AFTER render() for two reasons: IStartUp services (Localization among them) have
        // run their setUp by then, so t() has its resources; and resolve() on its own runs no init
        // or mounting hooks, so building this second instance renders nothing and costs nothing.
        // Email components are transient, so this is a fresh instance built from the same params.
        $component = $engine->resolve($name, $params);

        $text = $component instanceof IPlainTextEmail
            ? $component->text()
            : EmailConnector::htmlToPlainText($html);

        return new RenderedEmail($html, $text);
    }
}

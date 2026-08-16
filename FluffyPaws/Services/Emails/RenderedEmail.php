<?php

namespace FluffyPaws\Services\Emails;

/**
 * The two bodies of one email: the HTML part and the text/plain alternative.
 *
 * Kept as a type rather than a two-element array so a mailer cannot pass them in the wrong order —
 * both are strings, and swapping them sends raw HTML as the text part with no error anywhere.
 */
class RenderedEmail
{
    public function __construct(public string $html, public string $text)
    {
    }
}

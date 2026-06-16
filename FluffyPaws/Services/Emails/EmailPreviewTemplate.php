<?php

namespace FluffyPaws\Services\Emails;

/**
 * One previewable email template: a key (used in the preview URL), a human label
 * for the dropdown, and a renderer that produces the HTML body from a per-request
 * EmailPreviewContext.
 */
class EmailPreviewTemplate
{
    /**
     * @param callable(EmailPreviewContext): (string|object) $renderer returns the
     *        rendered HTML body, or a render Response whose ->body holds it
     */
    public function __construct(
        public string $key,
        public string $label,
        public $renderer,
    ) {
    }
}

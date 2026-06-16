<?php

namespace FluffyPaws\Services\Emails;

/**
 * Contributes previewable email templates to the /admin/email-templates page.
 *
 * Implement and register against this interface (one binding per package) — Paws
 * and each app add their own. EmailPreviewRegistry resolves every binding via
 * serviceProvider->getAll(), so no central list or startup wiring needs editing.
 */
interface IEmailPreviewProvider
{
    /** @return EmailPreviewTemplate[] */
    public function templates(): array;
}

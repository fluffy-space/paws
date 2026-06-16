<?php

namespace FluffyPaws\Services\Emails;

/**
 * The Paws layer's built-in previewable email templates.
 */
class PawsEmailPreviewProvider implements IEmailPreviewProvider
{
    public function templates(): array
    {
        return [
            new EmailPreviewTemplate(
                'reset-password',
                'Reset password',
                fn(EmailPreviewContext $ctx) => $ctx->emailService->getSendPasswordResetEmail($ctx->demoUser(), $ctx->demoCode())
            ),
            new EmailPreviewTemplate(
                'confirm-email',
                'Confirm email',
                fn(EmailPreviewContext $ctx) => $ctx->emailService->getUserActivateEmail($ctx->demoUser(), $ctx->demoCode())
            ),
        ];
    }
}

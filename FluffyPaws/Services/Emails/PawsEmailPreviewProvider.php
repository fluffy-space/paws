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
                // ->html: these now render to a RenderedEmail (HTML + authored text/plain), and the
                // preview page shows the HTML one.
                fn(EmailPreviewContext $ctx) => $ctx->emailService->getSendPasswordResetEmail($ctx->demoUser(), $ctx->demoCode())->html
            ),
            new EmailPreviewTemplate(
                'confirm-email',
                'Confirm email',
                fn(EmailPreviewContext $ctx) => $ctx->emailService->getUserActivateEmail($ctx->demoUser(), $ctx->demoCode())->html
            ),
            new EmailPreviewTemplate(
                'two-factor-changed',
                'Two-factor sign-in turned on',
                fn(EmailPreviewContext $ctx) => $ctx->emailService->getTwoFactorChangedEmail($ctx->demoUser(), true)->html
            ),
            new EmailPreviewTemplate(
                'two-factor-changed-off',
                'Two-factor sign-in turned off',
                fn(EmailPreviewContext $ctx) => $ctx->emailService->getTwoFactorChangedEmail($ctx->demoUser(), false)->html
            ),
            new EmailPreviewTemplate(
                'two-factor-changed-reset',
                'Two-factor sign-in reset by an admin',
                fn(EmailPreviewContext $ctx) => $ctx->emailService->getTwoFactorChangedEmail($ctx->demoUser(), false, true)->html
            ),
        ];
    }
}

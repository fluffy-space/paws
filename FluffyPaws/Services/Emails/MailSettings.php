<?php

namespace FluffyPaws\Services\Emails;

use Fluffy\Services\Settings\SettingDefinition;
use Fluffy\Services\Settings\SettingsRegistry;

/**
 * Deliverability knobs for the mail layer (settings store, group `email`),
 * editable live on the admin settings page.
 *
 * Settings and not config on purpose: which SNS topic feeds our webhook is
 * decided in the AWS console *after* a deploy, and turning signature checking
 * off is a thing you do on a dev box, not in a release.
 *
 * Called once per worker at boot from PawsStartUp::configureServices.
 */
class MailSettings
{
    /**
     * SNS topic ARNs allowed to drive the suppression list. One per line (commas
     * work too). Empty = the webhook accepts nothing.
     */
    public const SNS_TOPIC_ARNS = 'email.snsTopicArns';

    /** Verify the SNS signature on every delivery. Off is for offline dev only. */
    public const SNS_VERIFY_SIGNATURE = 'email.snsVerifySignature';

    public static function register(): void
    {
        SettingsRegistry::define(new SettingDefinition(
            key: self::SNS_TOPIC_ARNS,
            type: 'string',
            group: 'email',
            label: 'SNS topic ARNs (bounces & complaints)',
            description: 'One ARN per line (or comma-separated) — the SNS topics whose notifications may '
                . 'add addresses to the suppression list, e.g. '
                . 'arn:aws:sns:eu-central-1:123456789012:ses-bounces. Blank means the webhook accepts '
                . 'nothing: a valid AWS signature only proves a message came from SNS, not that it came '
                . 'from *our* topic, and without this list anyone with an AWS account could subscribe our '
                . 'endpoint and suppress addresses at will. The ARN of an unlisted topic is logged when it '
                . 'first tries to confirm, so setting this up is copy-paste.',
        ));
        SettingsRegistry::define(new SettingDefinition(
            key: self::SNS_VERIFY_SIGNATURE,
            type: 'boolean',
            group: 'email',
            label: 'Verify SNS signatures',
            description: 'Leave ON in production. Verification fetches the signing certificate from '
                . 'sns.<region>.amazonaws.com, so the only reason to turn it off is replaying captured '
                . 'payloads on a machine with no route to AWS.',
            initial: true,
        ));
    }
}

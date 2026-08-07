<?php

namespace FluffyPaws\Controllers;

use Fluffy\Controllers\BaseController;
use Fluffy\Domain\Message\HttpContext;
use FluffyPaws\Services\Emails\SesNotificationService;

/**
 * Public, unauthenticated receiver for Amazon SES bounce/complaint notifications
 * delivered over SNS (mirrors the Paddle webhook's placement — outside every auth
 * block, verified inside).
 *
 * Reads the RAW body: the SNS signature is computed over specific fields of the
 * exact JSON envelope, so a parsed/re-encoded model would not do.
 */
class SesNotificationController extends BaseController
{
    public function __construct(private SesNotificationService $notifications) {}

    public function Handle(HttpContext $httpContext)
    {
        $raw = $httpContext->request->getBody();
        // SNS repeats the envelope's Type here; used only when the body is unusable.
        $type = $httpContext->request->getHeader('x-amz-sns-message-type');

        $result = $this->notifications->receive(
            is_string($raw) ? $raw : '',
            is_string($type) ? $type : ''
        );

        return match ($result) {
            // 2xx tells SNS the delivery landed; anything else and it retries.
            'ok', 'confirmed', 'ignored' => ['received' => true],
            'bad_signature' => $this->Unauthorized('Invalid SNS signature.'),
            'forbidden_topic' => $this->Forbidden('Topic is not allowed.'),
            default => $this->BadRequest(['Invalid SNS payload.']),
        };
    }
}

<?php

namespace FluffyPaws\Controllers;

use DotDi\DependencyInjection\Container;
use Fluffy\Controllers\BaseController;
use Fluffy\Data\Entities\Auth\UserEntity;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Domain\Configuration\Config;
use Fluffy\Domain\Message\HttpContext;
use Fluffy\Services\Auth\AuthorizationService;
use Fluffy\Services\Auth\TotpService;
use Fluffy\Services\Auth\TwoFactorService;
use Fluffy\Services\Qr\IQrCodeRenderer;
use Fluffy\Swoole\RateLimit\IRateLimitService;
use FluffyPaws\Services\Emails\EmailService;
use SharedPaws\Models\Auth\TwoFactorConfirmModel;
use SharedPaws\Models\Auth\TwoFactorRecoveryCodesModel;
use SharedPaws\Models\Auth\TwoFactorSetupModel;
use SharedPaws\Models\Auth\TwoFactorStatusModel;
use SharedPaws\Models\Auth\UserViewModel;

/**
 * /api/account/two-factor: the signed-in user turns two-factor sign-in on and off and renews
 * recovery codes. Always their own account: no user id is ever taken from the request.
 *
 * Every change is confirmed, so a session alone is not enough: setup asks for the password,
 * turning off and new codes ask for a current code (and turning off the password too). An
 * account with no password (provider sign-in) is confirmed by the code alone. Nothing here
 * works while an admin is viewing as the user.
 */
class TwoFactorController extends BaseController
{
    function __construct(
        protected AuthorizationService $auth,
        protected TwoFactorService $twoFactor,
        protected Config $config,
        protected EmailService $emails,
        protected IMapper $mapper
    ) {}

    /** GET /api/account/two-factor */
    public function Status()
    {
        $user = $this->auth->getAuthorizedUser();
        if ($user === null) {
            return $this->Unauthorized();
        }
        $status = new TwoFactorStatusModel();
        $status->Enabled = $this->twoFactor->isEnabled($user->Id);
        $status->RecoveryCodesLeft = $status->Enabled ? $this->twoFactor->recoveryCodesLeft($user->Id) : 0;
        $status->HasPassword = $this->hasPassword($user);
        $status->Required = $this->auth->twoFactorRequiredFor($user);
        return $status;
    }

    /** POST /api/account/two-factor/setup {Password}: a new secret to scan; not in force until Enable. */
    public function Setup(TwoFactorConfirmModel $model, IRateLimitService $rateLimit, HttpContext $httpContext, TotpService $totp, Container $container)
    {
        $user = $this->actingUser();
        if (!$user instanceof UserEntity) {
            return $user;
        }
        if (!$rateLimit->limit('tfa:ip:' . $httpContext->request->getIp(), 10, 5 * 60)) {
            return $this->TooManyRequests('Too many attempts. Wait a few minutes and try again.');
        }
        if ($this->hasPassword($user) && !password_verify((string) $model->Password, (string) $user->Password)) {
            return $this->BadRequest(['Your password is incorrect.']);
        }
        $secret = $this->twoFactor->beginSetup($user->Id);
        if ($secret === null) {
            return $this->BadRequest(['Two-factor sign-in is already on.']);
        }
        $setup = new TwoFactorSetupModel();
        $setup->Secret = trim(chunk_split($secret, 4, ' '));
        $renderers = $container->serviceProvider->getAll(IQrCodeRenderer::class);
        if (count($renderers) > 0) {
            $issuer = (string) ($this->config->values['twoFactorIssuer'] ?? 'Sign-in');
            $uri = $totp->provisioningUri($secret, (string) ($user->Email ?? $user->UserName), $issuer);
            $setup->QrDataUri = 'data:image/svg+xml;base64,' . base64_encode($renderers[0]->toSvg($uri));
        }
        return $setup;
    }

    /** POST /api/account/two-factor/enable {Code}: the first code from the app turns it on. */
    public function Enable(TwoFactorConfirmModel $model)
    {
        $user = $this->actingUser();
        if (!$user instanceof UserEntity) {
            return $user;
        }
        $codes = $this->twoFactor->confirmSetup($user->Id, trim((string) $model->Code));
        if ($codes === TwoFactorService::RESULT_TOO_MANY) {
            return $this->tooManyCodes();
        }
        if (!is_array($codes)) {
            return $this->BadRequest(['That code didn\'t work. Use the 6-digit code the app shows now.']);
        }
        // Sessions opened before this had only the password behind them.
        $this->auth->revokeOtherSessions($user->Id);
        $this->emails->dispatchTwoFactorChangedEmail($this->mapper->map(UserViewModel::class, $user), true);
        $result = new TwoFactorRecoveryCodesModel();
        $result->Codes = $codes;
        return $result;
    }

    /** POST /api/account/two-factor/recovery-codes {Code}: new codes; the old ones stop working. */
    public function RecoveryCodes(TwoFactorConfirmModel $model)
    {
        $user = $this->actingUser();
        if (!$user instanceof UserEntity) {
            return $user;
        }
        $check = $this->twoFactor->verifyCode($user->Id, trim((string) $model->Code));
        if ($check === TwoFactorService::RESULT_TOO_MANY) {
            return $this->tooManyCodes();
        }
        if ($check !== TwoFactorService::RESULT_OK) {
            return $this->BadRequest(['That code didn\'t work. Use the code the app shows now, or a recovery code.']);
        }
        $result = new TwoFactorRecoveryCodesModel();
        $result->Codes = $this->twoFactor->replaceRecoveryCodes($user->Id);
        return $result;
    }

    /** POST /api/account/two-factor/disable {Password, Code} */
    public function Disable(TwoFactorConfirmModel $model, IRateLimitService $rateLimit, HttpContext $httpContext)
    {
        $user = $this->actingUser();
        if (!$user instanceof UserEntity) {
            return $user;
        }
        if (!$rateLimit->limit('tfa:ip:' . $httpContext->request->getIp(), 10, 5 * 60)) {
            return $this->TooManyRequests('Too many attempts. Wait a few minutes and try again.');
        }
        if ($this->auth->twoFactorRequiredFor($user)) {
            return $this->BadRequest(['Accounts with admin access must keep two-factor sign-in on.']);
        }
        if ($this->hasPassword($user) && !password_verify((string) $model->Password, (string) $user->Password)) {
            return $this->BadRequest(['Your password is incorrect.']);
        }
        $check = $this->twoFactor->verifyCode($user->Id, trim((string) $model->Code));
        if ($check === TwoFactorService::RESULT_TOO_MANY) {
            return $this->tooManyCodes();
        }
        if ($check !== TwoFactorService::RESULT_OK) {
            return $this->BadRequest(['That code didn\'t work. Use the code the app shows now, or a recovery code.']);
        }
        $this->twoFactor->disable($user->Id);
        $this->emails->dispatchTwoFactorChangedEmail($this->mapper->map(UserViewModel::class, $user), false);
        return ['success' => true];
    }

    /** The signed-in user, or the response to send instead. */
    private function actingUser()
    {
        $user = $this->auth->getAuthorizedUser();
        if ($user === null) {
            return $this->Unauthorized();
        }
        if ($this->auth->isImpersonating()) {
            return $this->Forbidden('Not available while viewing as another user.');
        }
        return $user;
    }

    private function tooManyCodes()
    {
        return $this->TooManyRequests('Too many wrong codes. Wait 15 minutes and try again.');
    }

    private function hasPassword(UserEntity $user): bool
    {
        return $user->Password !== null && $user->Password !== '';
    }
}

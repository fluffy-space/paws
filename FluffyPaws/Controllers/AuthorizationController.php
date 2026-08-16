<?php

namespace FluffyPaws\Controllers;

use Fluffy\Controllers\BaseController;
use Fluffy\Data\Entities\Auth\UserEntity;
use Fluffy\Data\Entities\Auth\UserEntityMap;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Data\Repositories\UserRepository;
use Fluffy\Domain\Message\HttpContext;
use Fluffy\Security\Capability;
use Fluffy\Security\Permissions;
use Fluffy\Services\Auth\AuthorizationService;
use Fluffy\Swoole\RateLimit\IRateLimitService;
use FluffyPaws\Services\Emails\EmailService;
use FluffyPaws\Services\Localization\LocalizationService;
use SharedPaws\Models\Auth\ConfirmEmailModel;
use SharedPaws\Models\Auth\LoginModel;
use SharedPaws\Models\Auth\LoginValidation;
use SharedPaws\Models\Auth\RegisterModel;
use SharedPaws\Models\Auth\RegisterValidation;
use SharedPaws\Models\Auth\ResetPasswordModel;
use SharedPaws\Models\Auth\ResetPasswordValidation;
use SharedPaws\Models\Auth\UserAuthSessionModel;
use SharedPaws\Models\Auth\UserViewModel;
use SharedPaws\Validation\ValidationRules;

class AuthorizationController extends BaseController
{
    function __construct(
        protected AuthorizationService $auth,
        protected IMapper $mapper,
        protected EmailService $emailService
    ) {}

    public function Me()
    {
        $user = $this->auth->getAuthorizedUser();
        $response = new UserAuthSessionModel();
        if ($user !== null) {
            $response->user = $this->mapper->map(UserViewModel::class, $user);
            $response->user->CanAccessAdmin = Permissions::can($user->Permissions, Capability::AccessAdmin);
            $response->roles = Permissions::roleNames($user->Permissions);
            $response->capabilities = Permissions::capabilityNames($user->Permissions);
            $response->isAuthenticated = true;
            // Impersonation overlay: $user is the effective (target) user; surface
            // the acting admin so the client can render the "viewing as" banner.
            if ($this->auth->isImpersonating()) {
                $response->impersonating = true;
                $real = $this->auth->getRealUser();
                $name = $real ? trim(($real->FirstName ?? '') . ' ' . ($real->LastName ?? '')) : '';
                $response->impersonatorName = $name !== '' ? $name : ($real?->UserName ?? null);
            }
        }
        return $response;
    }

    public function Session()
    {
        $session = $this->auth->getOrStartSession();
        return ['CSRFToken' => $session->CSRF];
    }

    public function Logout()
    {
        $this->auth->logout();
        return ['success' => true];
    }

    public function Login(LoginModel $loginModel, LocalizationService $localization, IRateLimitService $rateLimit, HttpContext $httpContext)
    {
        if (!$this->auth->authorizeCSRF()) {
            return $this->Forbidden('Invalid CSRF-token.');
        }

        if (!$rateLimit->limit($httpContext->request->getIp(), 10, 5 * 60)) {
            return $this->TooManyRequests($localization->localize('rate-limit.too-many-requests'));
        }

        $validationRules = (new LoginValidation($loginModel, fn(string $key) => $localization->localize($key)))->getValidationRules();
        // replace rule
        // $validationRules['Email']['email']  = function () use ($loginModel) {
        //     return (!$loginModel->Email || filter_var($loginModel->Email, FILTER_VALIDATE_EMAIL)) ? true : 'Email is in wrong format, please check again.';
        // };
        $validationMessages = [];
        foreach ($validationRules as $property => $rules) {
            foreach ($rules as $validationRule) {
                $validationResult = $validationRule();
                if ($validationResult !== true) {
                    $validationMessages[] = $validationResult === false ? "Validation has failed for $property." : $validationResult;
                }
            }
        }
        if (count($validationMessages) > 0) {
            return $this->BadRequest($validationMessages);
        }
        $result = $this->auth->authorizeBasic($loginModel->Email, $loginModel->Password);
        if ($result->Disabled) {
            return $this->Forbidden('This account has been disabled.');
        }
        if (!$result->Success) {
            return $this->Unauthorized($localization->localize('login.validation.wrong-username-or-password'));
        }
        $this->auth->authorizeUser($result->User, $loginModel->RememberMe);
        return ['success' => true];
    }

    public function Register(RegisterModel $registerModel, LocalizationService $localization, IRateLimitService $rateLimit, HttpContext $httpContext)
    {
        if (!$this->auth->authorizeCSRF()) {
            return $this->Forbidden('Invalid CSRF-token.');
        }
        $validationMessages = [];
        $validationRules = (new RegisterValidation($registerModel, fn(string $key) => $localization->localize($key)))->getValidationRules();
        // replace rule
        // $validationRules['Email']['email']  = function () use ($registerModel) {
        //     return (!$registerModel->Email || filter_var($registerModel->Email, FILTER_VALIDATE_EMAIL)) ? true : 'Email is in wrong format, please check again.';
        // };
        foreach ($validationRules as $property => $rules) {
            foreach ($rules as $validationRule) {
                $validationResult = $validationRule();
                if ($validationResult !== true) {
                    $validationMessages[] = $validationResult === false ? "Validation has failed for $property." : $validationResult;
                }
            }
        }
        if (count($validationMessages) > 0) {
            return $this->BadRequest($validationMessages);
        }

        if (!$rateLimit->limit($httpContext->request->getIp(), 10, 5 * 60)) {
            return $this->TooManyRequests($localization->localize('rate-limit.too-many-requests'));
        }

        // DB validations
        $user = $this->mapper->map(UserEntity::class, $registerModel);
        $registerResult = $this->auth->registerUser($user);
        if (!$registerResult->Success) {
            if ($registerResult->UserNameTaken) {
                $validationMessages[] = $localization->localize('register.validation.user-exists'); //'Sorry, user with this email already exists. Try to login or click "I forgot my password".';
            }
            if (count($validationMessages) === 0) { // failed due to server error
                $validationMessages[] = $localization->localize('register.validation.failed'); //'Sorry, something went wrong. Please try again later.';
            }
            return $this->BadRequest($validationMessages);
        }
        // authorize
        $this->auth->authorizeUser($registerResult->User, true);
        if (!$registerResult->User->EmailConfirmed) {
            $verificationCode = $this->auth->createVerificationCode($registerResult->User->Id);
            // send activation email
            $this->emailService->dispatchUserActivateEmail($this->mapper->map(UserViewModel::class, $user), $verificationCode->Code);
        }
        return ['success' => true];
    }

    /**
     * Send the activation email again to the signed-in user's own address.
     *
     * The activation code lives 3 days and the mail can land in spam, so without this the
     * only way out of an unconfirmed account is a support ticket. Deliberately scoped to the
     * session user — no address is accepted from the request, so this cannot be used to mail
     * a stranger. Idempotent: an already-confirmed account returns success and sends nothing.
     */
    public function ResendVerification(LocalizationService $localization, IRateLimitService $rateLimit, HttpContext $httpContext)
    {
        if (!$this->auth->authorizeCSRF()) {
            return $this->Forbidden('Invalid CSRF-token.');
        }

        $user = $this->auth->getAuthorizedUser();
        if ($user === null) {
            return $this->Unauthorized('Please sign in first.');
        }

        // An admin viewing as someone else must not be able to fire mail at that person.
        if ($this->auth->isImpersonating()) {
            return $this->Forbidden('Not available while impersonating.');
        }

        if ($user->EmailConfirmed) {
            return ['success' => true];
        }

        // Two buckets: one per account (the real limit — a mailbox nobody can drain by
        // switching IP) and one per IP (a signup farm resending across many fresh accounts).
        // Both keys are prefixed so they do not share the login/register bucket, which is
        // keyed by the bare IP.
        if (
            !$rateLimit->limit("resend-verification:user:{$user->Id}", 3, 15 * 60)
            || !$rateLimit->limit('resend-verification:ip:' . $httpContext->request->getIp(), 10, 15 * 60)
        ) {
            return $this->TooManyRequests($localization->localize('rate-limit.too-many-requests'));
        }

        $verificationCode = $this->auth->createVerificationCode($user->Id);
        $this->emailService->dispatchUserActivateEmail($this->mapper->map(UserViewModel::class, $user), $verificationCode->Code);
        return ['success' => true];
    }

    /**
     * Confirm an email address from the code in the activation mail.
     *
     * POST, and deliberately so. This used to be the GET the emailed link pointed at, which
     * meant anything that fetches links in transit confirmed the address before the human
     * ever saw the mail — Microsoft Defender (Safe Links) scans every URL in inbound mail on
     * delivery, so Outlook/Hotmail signups landed already confirmed with the one-shot code
     * burnt, and the real click then hit the failure page. The link now opens a page
     * (ConfirmEmailPage) whose button posts here: scanners issue GETs, they do not submit.
     *
     * Still code-only, no session required — the mail is often opened on another device.
     */
    public function ConfirmEmail(ConfirmEmailModel $confirmModel, LocalizationService $localization, IRateLimitService $rateLimit, HttpContext $httpContext)
    {
        if (!$this->auth->authorizeCSRF()) {
            return $this->Forbidden('Invalid CSRF-token.');
        }

        // Prefixed so it does not share the bare-IP login/register bucket. The code is 32
        // random chars, so this is not what stops guessing — it caps a client hammering the
        // endpoint, and it is loose enough that a page reload or two never trips it.
        if (!$rateLimit->limit('confirm-email:ip:' . $httpContext->request->getIp(), 20, 5 * 60)) {
            return $this->TooManyRequests($localization->localize('rate-limit.too-many-requests'));
        }

        $userCode = $confirmModel->Code ? $this->auth->verifyCode($confirmModel->Code) : null;
        if ($userCode === null) {
            return $this->BadRequest([$localization->localize('confirm-email.failed')]);
        }
        $this->auth->activateUser($userCode->UserId);
        $this->auth->invalidateCode($userCode);
        return ['success' => true];
    }

    public function ResetPassword(string $Email, UserRepository $users, LocalizationService $localization, IRateLimitService $rateLimit, HttpContext $httpContext)
    {
        if (!$this->auth->authorizeCSRF()) {
            return $this->Forbidden('Invalid CSRF-token.');
        }

        if (!$rateLimit->limit($httpContext->request->getIp(), 10, 5 * 60)) {
            return $this->TooManyRequests($localization->localize('rate-limit.too-many-requests'));
        }

        $validationMessages = [];
        $validationRules = ValidationRules::rules((object)['email' => $Email])
            ->required('email', $localization->localize('register.validation.email-required'))
            ->email('email', $localization->localize('register.validation.wrong-email'))
            ->toList();

        foreach ($validationRules as $property => $rules) {
            foreach ($rules as $validationRule) {
                $validationResult = $validationRule();
                if ($validationResult !== true) {
                    $validationMessages[] = $validationResult === false ? "Validation has failed for $property." : $validationResult;
                }
            }
        }
        if (count($validationMessages) > 0) {
            return $this->BadRequest($validationMessages);
        }
        $user = $users->find(UserEntityMap::PROPERTY_UserName, $Email);
        if ($user === null) {
            // nothing to send
            return ['success' => true];
        }
        $verificationCode = $this->auth->createVerificationCode($user->Id);
        // send activation email
        $this->emailService->dispatchPasswordResetEmail($this->mapper->map(UserViewModel::class, $user), $verificationCode->Code);
        return ['success' => true];
    }

    public function ResetPasswordConfirm(ResetPasswordModel $resetPasswordModel, LocalizationService $localization)
    {
        if (!$this->auth->authorizeCSRF()) {
            return $this->Forbidden('Invalid CSRF-token.');
        }
        $validationMessages = [];
        $validationRules = (new ResetPasswordValidation($resetPasswordModel, fn(string $key) => $localization->localize($key)))->getValidationRules();
        $validationRules['Code'] = ['required' => fn() => $resetPasswordModel->Code ? true : 'Verification code is required.'];
        foreach ($validationRules as $property => $rules) {
            foreach ($rules as $validationRule) {
                $validationResult = $validationRule();
                if ($validationResult !== true) {
                    $validationMessages[] = $validationResult === false ? "Validation has failed for $property." : $validationResult;
                }
            }
        }
        if (count($validationMessages) > 0) {
            return $this->BadRequest($validationMessages);
        }
        $userCode = $this->auth->verifyCode($resetPasswordModel->Code);
        if ($userCode !== null) {
            $this->auth->changePassword($userCode->UserId, $resetPasswordModel->Password);
            $this->auth->invalidateCode($userCode);
            // ?? send email with warning about changed password
            return ['success' => true];
        }
        return $this->BadRequest([$localization->localize('reset-password.reset-failed')]);
        // 'Password reset failed. Perhaps reset code expired or changing password is not allowed for inactive users.']);
    }
}

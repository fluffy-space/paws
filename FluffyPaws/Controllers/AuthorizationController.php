<?php

namespace FluffyPaws\Controllers;

use Fluffy\Controllers\BaseController;
use Fluffy\Data\Entities\Auth\UserEntity;
use Fluffy\Data\Entities\Auth\UserEntityMap;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Data\Repositories\UserRepository;
use Fluffy\Domain\Message\HttpContext;
use Fluffy\Services\Auth\AuthorizationService;
use Fluffy\Swoole\RateLimit\RateLimitService;
use FluffyPaws\Services\Emails\EmailService;
use FluffyPaws\Services\Localization\LocalizationService;
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
            $response->isAuthenticated = true;
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

    public function Login(LoginModel $loginModel, LocalizationService $localization, RateLimitService $rateLimit, HttpContext $httpContext)
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
        if (!$result->Success) {
            return $this->Unauthorized($localization->localize('login.validation.wrong-username-or-password'));
        }
        $this->auth->authorizeUser($result->User, $loginModel->RememberMe);
        return ['success' => true];
    }

    public function Register(RegisterModel $registerModel, LocalizationService $localization, RateLimitService $rateLimit, HttpContext $httpContext)
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

    public function ConfirmEmail(string $code)
    {
        $userCode = $this->auth->verifyCode($code);
        if ($userCode !== null) {
            $this->auth->activateUser($userCode->UserId);
            $this->auth->invalidateCode($userCode);
            // redirect to success
            return $this->Redirect('/account/verified');
        }
        // redirect to failed
        return $this->Redirect('/account/verified/failed');
    }

    public function ResetPassword(string $Email, UserRepository $users, LocalizationService $localization, RateLimitService $rateLimit, HttpContext $httpContext)
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

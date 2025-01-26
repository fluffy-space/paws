<?php

namespace FluffyPaws\Migrations\Localization;

use Fluffy\Data\Entities\CommonMap;
use Fluffy\Data\Repositories\MigrationRepository;
use Fluffy\Migrations\BaseMigration;
use FluffyPaws\Data\Entities\Localization\LocaleResourceEntity;
use FluffyPaws\Data\Entities\Localization\LocaleResourceEntityMap;
use FluffyPaws\Data\Repositories\LocaleResourceRepository;

class LocaleEnglishMigration extends BaseMigration
{
    function __construct(MigrationRepository $MigrationHistoryRepository, private LocaleResourceRepository $localeResources)
    {
        parent::__construct($MigrationHistoryRepository);
    }

    public function up()
    {
        $languageId = 1;
        $where = [
            [LocaleResourceEntityMap::PROPERTY_LanguageId, 1]
        ];
        $entities = $this->localeResources->search($where, [LocaleResourceEntityMap::PROPERTY_CreatedOn => 1], 1, null, false);
        $resources = $entities['list'];
        $models = [];
        $models = array_reduce($resources, function (array $models, LocaleResourceEntity $entity) {
            $models[$entity->Name] = $entity->Value;
            return $models;
        }, $models);
        $toAdd = $this->getResources();
        $added = 0;
        $skipped = 0;
        foreach ($toAdd as $key => $value) {
            if (!isset($models[$key])) {
                $resource = new LocaleResourceEntity();
                $resource->LanguageId = $languageId;
                $resource->Name = $key;
                $resource->Value = $value;
                $this->localeResources->create($resource);
                $added++;
            } else {
                $skipped++;
            }
        }
        echo "LocaleEnglishMigration: $added added, skipped $skipped" . PHP_EOL;
    }

    public function down() {}

    public function getResources(): array
    {
        return [
            'layout.title' => 'Paws',
            'layout.page-not-found' => 'Page not found',
            'login.title' => 'Login',
            'login.header' => 'Enter your credentials',
            'login.email-or-phone' => 'Email or phone number',
            'login.forgot-your-password' => 'Forgot your password?',
            'login.reset-here' => 'Reset it here.',
            'login.password' => 'Password',
            'login.remember-me' => 'Remember me?',
            'login.authorize-button' => 'Login',
            'login.do-not-have-account' => 'Don\'t have an account?',
            'login.register-here' => 'Register here.',
            'login.validation.email-or-phone-required' => 'Email or phone is required.',
            'login.validation.password-required' => 'Password is required.',
            'login.validation.wrong-email' => 'Wrong email.',
            'login.validation.wrong-phone' => 'Wrong phone number.',
            'login.validation.wrong-username-or-password' => 'Wrong username or password.',
            'register.title' => 'Register',
            'register.header' => 'Enter your details',
            'register.first-name' => 'First name',
            'register.last-name' => 'Last name',
            'register.email' => 'Email',
            'register.phone' => 'Phone number',
            'register.password-confirm' => 'Password confirmation',
            'register.register-button' => 'Register',
            'register.have-account' => 'Already have an account?',
            'register.login-here' => 'Login here.',
            'register.validation.failed' => 'Registration has failed.',
            'register.validation.first-name-required' => 'First name is required.',
            'register.validation.last-name-required' => 'Last name is required.',
            'register.validation.wrong-email' => 'Wrong email.',
            'register.validation.wrong-phone' => 'Wrong phone number.',
            'register.validation.password-confirmation-required' => 'Password confirmation is required.',
            'register.validation.password-confirmation-match' => 'Password confirmation should match Password.',
            'register.validation.user-exists' => 'Sorry, user with this email already exists. Try to login or click "I forgot my password".',
            'reset-password.title' => 'Reset your password',
            'reset-password.guide' => 'Enter your email and we will send you a password reset link.',
            'reset-password.send-link' => 'Send the link',
            'reset-password.back-to' => 'Back to',
            'reset-password.login-page' => 'login page.',
            'reset-password.email-sent' => 'Email has been sent.',
            'reset-password.email-sent-to' => 'We just sent you an email with instructions to {email}.',
            'reset-password.email-sent-warning' => 'If you don\'t see an email for quite a while please make sure your email is registered in our system.',
            'reset-password.email-sent-spam-message' => 'Do not forget to check your spam folder, sometimes it happens. You may also contact us if you need any help.',
            'register.validation.email-required' => 'Email is required.',
            'reset-password.something-went-wrong' => 'Something went wrong, please try again later.',
            'reset-password.already-changed' => 'Already changed your password?',
            'reset-password.change-password' => 'Change password',
            'reset-password.header' => 'Your password has been changed.',
            'reset-password.reset-failed' => 'Password reset failed. Perhaps reset code expired or changing password is not allowed for inactive users.',
            'login' => 'Login',
            'logout' => 'Logout',
            'rate-limit.too-many-requests' => 'Too many attempts, please try again later.'
        ];
    }
}

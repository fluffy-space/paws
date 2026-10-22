<?php

namespace Pupils\Components\Views\Auth;

use Pupils\Components\Services\Localization\HasLocalization;
use Viewi\Components\BaseComponent;

/**
 * The heading above the registration form. On its own it renders the generic
 * `register.header` line, which is all the framework can say.
 *
 * It is a component of its own so an app can replace just this part with #[OverrideComponent]:
 * say what the account is for, echo the page the visitor came from, and keep the form logic in
 * Register untouched. Register passes nothing in; an override reads what it needs (such as the
 * `?redirect=` query) from the route itself.
 */
class RegisterIntro extends BaseComponent
{
    use HasLocalization;
}

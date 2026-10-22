<?php

namespace Pupils\Components\Views\Auth;

use Viewi\Components\BaseComponent;

/**
 * A line under the registration button. Empty here, so it renders nothing; an app overrides it
 * with #[OverrideComponent] to answer a worry at the moment of signing up (what will you email me?).
 */
class RegisterNote extends BaseComponent
{
    public string $text = '';
}

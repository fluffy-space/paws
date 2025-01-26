<?php

namespace Pupils\Components\Services\Localization;

use Viewi\DI\Inject;
use Viewi\DI\Scope;

trait HasLocalization
{
    #[Inject(Scope::SINGLETON)]
    public Localization $localization;

    protected $_translateFunction = null;

    public function translateFn()
    {
        return $this->_translateFunction ?? ($this->_translateFunction = fn(string $key) => $this->localization->t($key));
    }
}

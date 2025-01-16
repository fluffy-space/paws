<?php

namespace FluffyPaws\Controllers;

use FluffyPaws\Services\Localization\LocalizationService;
use Fluffy\Controllers\BaseController;

class LocalizationController extends BaseController
{
    public function __construct(
        protected LocalizationService $localization
    ) {
    }

    public function GetResources(int $languageId)
    {
        return $this->localization->getResources($languageId);
    }
}

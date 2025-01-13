<?php

namespace Pupils;

use Viewi\Packages\ViewiPackage;
use Viewi\UI\ViewiUI;

class FluffyPupils extends ViewiPackage
{
    public static function getComponentsPath(): array
    {
        return [__DIR__ . DIRECTORY_SEPARATOR . 'Components', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SharedPaws'];
    }

    public static function jsDir(): ?string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'js';
    }

    public static function jsModulePackagePath(): ?string
    {
        return 'pupils';
    }

    public static function name(): string
    {
        return 'pupils';
    }

    public static function assetsPath(): ?string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'assets';
    }

    public static function getDependencies(): array
    {
        return [ViewiUI::class];
    }
}

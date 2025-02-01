<?php

namespace Pupils\Components\Services\Dates;

use Viewi\Builder\Attributes\GlobalEntry;
use Viewi\DI\Singleton;

#[Singleton]
class DateHelper
{
    #[GlobalEntry]
    public function formatDate(int $milliseconds)
    {
        $seconds = $milliseconds / 1000000;
        return gmdate('Y-m-d', (int)$seconds); //  H:i:s
    }
}

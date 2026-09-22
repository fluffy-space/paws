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

    /** Admin lists: to the second, UTC, so events can be lined up against each other. */
    #[GlobalEntry]
    public function formatDateTime(int $micros)
    {
        return gmdate('Y-m-d H:i:s', (int)($micros / 1000000));
    }
}

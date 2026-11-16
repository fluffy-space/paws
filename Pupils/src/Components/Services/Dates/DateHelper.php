<?php

namespace Pupils\Components\Services\Dates;

use Viewi\Builder\Attributes\GlobalEntry;
use Viewi\DI\Singleton;

/** Template-wide date functions, in the signed-in person's time zone (UserClock). */
#[Singleton]
class DateHelper
{
    public function __construct(private UserClock $clock) {}

    /** A microsecond timestamp as a day. */
    #[GlobalEntry]
    public function formatDate(int $micros)
    {
        return $this->clock->dateMicros('Y-m-d', $micros);
    }

    /** Admin lists: to the second, so events can be lined up against each other. */
    #[GlobalEntry]
    public function formatDateTime(int $micros)
    {
        return $this->clock->dateMicros('Y-m-d H:i:s', $micros);
    }
}

<?php

namespace FluffyPaws\Services\Time;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * A person's time zone on the server: checking a zone name, formatting an instant in it, and the
 * offset table the browser formats with.
 *
 * Stored timestamps stay UTC. A zone is an IANA name (Europe/Kyiv); null or an unknown name reads
 * as UTC everywhere here, so a caller never has to check before formatting.
 */
class TimeZones
{
    public const UTC = 'UTC';

    /** How far back and ahead of now the offset table reaches, in years. */
    private const YEARS_BACK = 6;
    private const YEARS_AHEAD = 3;

    /**
     * The name if PHP knows it, else null. Old spellings a browser may still report (Europe/Kiev)
     * are accepted: PHP resolves them to the same rules as the current name.
     */
    public static function valid(?string $zone): ?string
    {
        $zone = trim((string) $zone);
        if ($zone === '' || strlen($zone) > 64) {
            return null;
        }
        return in_array($zone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true) ? $zone : null;
    }

    public static function zone(?string $zone): DateTimeZone
    {
        try {
            return new DateTimeZone(self::valid($zone) ?? self::UTC);
        } catch (Throwable) {
            return new DateTimeZone(self::UTC);
        }
    }

    /** date()-style format of a unix time in the zone. */
    public static function format(string $format, int $seconds, ?string $zone): string
    {
        return (new DateTimeImmutable('@' . $seconds))->setTimezone(self::zone($zone))->format($format);
    }

    /** Seconds east of UTC at that instant. */
    public static function offset(int $seconds, ?string $zone): int
    {
        return self::zone($zone)->getOffset(new DateTimeImmutable('@' . $seconds));
    }

    /** Unix time of 00:00 on a Y-m-d day in the zone, or null when the day does not parse. */
    public static function startOfDay(string $day, ?string $zone): ?int
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $day, self::zone($zone));
        if ($date === false || $date->format('Y-m-d') !== $day) {
            return null;
        }
        return $date->getTimestamp();
    }

    /**
     * The zone's offsets as [[fromUnixSeconds, offsetSeconds], ...], oldest first.
     *
     * The browser formats with this table instead of its own zone data, so a page reads the same
     * rendered on the server and in the browser. The first row applies to anything earlier and the
     * last to anything later. UTC and unknown zones give one row.
     *
     * @return int[][]
     */
    public static function offsets(?string $zone, ?int $now = null): array
    {
        $now ??= time();
        $from = $now - self::YEARS_BACK * 366 * 86400;
        $to = $now + self::YEARS_AHEAD * 366 * 86400;
        $transitions = self::zone($zone)->getTransitions($from, $to);
        if ($transitions === false || $transitions === []) {
            return [[0, 0]];
        }
        $rows = [];
        foreach ($transitions as $transition) {
            $rows[] = [(int) $transition['ts'], (int) $transition['offset']];
        }
        return $rows;
    }
}

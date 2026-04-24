<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

final class BookingListFilters
{
    public const PARAM_STATUS = 'status';

    public const PARAM_PERIOD = 'period';

    public const PARAM_CAR_ID = 'car_id';

    public const PARAM_DRIVER_ID = 'driver_id';

    public const STATUS_UPCOMING = 'upcoming';

    public const STATUS_PREVIOUS = 'previous';

    public const PERIOD_WEEK = 'week';

    public const PERIOD_MONTH = 'month';

    /**
     * @return array<int, string>
     */
    public static function allowedStatuses(): array
    {
        return [
            self::STATUS_UPCOMING,
            self::STATUS_PREVIOUS,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function allowedPeriods(): array
    {
        return [
            self::PERIOD_WEEK,
            self::PERIOD_MONTH,
        ];
    }

    /**
     * Upcoming means start_date >= reference time.
     * Previous means end_date < reference time.
     *
     * @return array{column: 'start_date'|'end_date', operator: '>='|'<'}
     */
    public static function statusConstraint(string $status): array
    {
        return match ($status) {
            self::STATUS_UPCOMING => ['column' => 'start_date', 'operator' => '>='],
            self::STATUS_PREVIOUS => ['column' => 'end_date', 'operator' => '<'],
            default => throw new InvalidArgumentException("Unsupported status filter [{$status}]."),
        };
    }

    /**
     * Returns [periodStart, periodEnd] using the current timezone.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public static function periodBounds(string $period, ?CarbonInterface $reference = null): array
    {
        $reference = CarbonImmutable::instance($reference ?? now());

        return match ($period) {
            self::PERIOD_WEEK => [$reference->startOfWeek(), $reference->endOfWeek()],
            self::PERIOD_MONTH => [$reference->startOfMonth(), $reference->endOfMonth()],
            default => throw new InvalidArgumentException("Unsupported period filter [{$period}]."),
        };
    }
}

<?php

use App\Support\BookingListFilters;
use Carbon\CarbonImmutable;

test('defines supported status filters', function () {
    expect(BookingListFilters::allowedStatuses())
        ->toBe([
            BookingListFilters::STATUS_UPCOMING,
            BookingListFilters::STATUS_PREVIOUS,
        ]);
});

test('defines supported period filters', function () {
    expect(BookingListFilters::allowedPeriods())
        ->toBe([
            BookingListFilters::PERIOD_WEEK,
            BookingListFilters::PERIOD_MONTH,
        ]);
});

test('maps upcoming and previous to the expected date constraints', function () {
    expect(BookingListFilters::statusConstraint(BookingListFilters::STATUS_UPCOMING))
        ->toBe(['column' => 'start_date', 'operator' => '>=']);

    expect(BookingListFilters::statusConstraint(BookingListFilters::STATUS_PREVIOUS))
        ->toBe(['column' => 'end_date', 'operator' => '<']);
});

test('computes week bounds from a reference date', function () {
    [$start, $end] = BookingListFilters::periodBounds(
        BookingListFilters::PERIOD_WEEK,
        CarbonImmutable::parse('2026-04-24 10:30:00')
    );

    expect($start->toDateTimeString())->toBe('2026-04-20 00:00:00');
    expect($end->toDateTimeString())->toBe('2026-04-26 23:59:59');
});

test('computes month bounds from a reference date', function () {
    [$start, $end] = BookingListFilters::periodBounds(
        BookingListFilters::PERIOD_MONTH,
        CarbonImmutable::parse('2026-04-24 10:30:00')
    );

    expect($start->toDateTimeString())->toBe('2026-04-01 00:00:00');
    expect($end->toDateTimeString())->toBe('2026-04-30 23:59:59');
});

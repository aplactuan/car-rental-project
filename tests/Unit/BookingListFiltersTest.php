<?php

use App\Support\BookingListFilters;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

test('defines supported status filters', function () {
    expect(BookingListFilters::allowedStatuses())
        ->toBe([
            BookingListFilters::STATUS_UPCOMING,
            BookingListFilters::STATUS_PREVIOUS,
        ]);
});

test('defines supported detailed status filters', function () {
    expect(BookingListFilters::allowedDetailedStatuses())
        ->toBe([
            BookingListFilters::STATUS_COMPLETED,
            BookingListFilters::STATUS_TODAY,
            BookingListFilters::STATUS_ONGOING,
            BookingListFilters::STATUS_INCOMING,
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

test('statusConstraint throws for unknown status', function () {
    BookingListFilters::statusConstraint('unknown');
})->throws(InvalidArgumentException::class);

test('applyDetailedStatusConstraint throws for unknown status', function () {
    $builder = Mockery::mock(Builder::class);

    BookingListFilters::applyDetailedStatusConstraint($builder, 'unknown');
})->throws(InvalidArgumentException::class);

test('applyDetailedStatusConstraint applies completed constraint', function () {
    Carbon::setTestNow('2026-05-29 12:00:00');

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->once()->with('end_date', '<', Mockery::type('Illuminate\Support\Carbon'))->andReturnSelf();

    BookingListFilters::applyDetailedStatusConstraint($builder, BookingListFilters::STATUS_COMPLETED);

    Carbon::setTestNow();
});

test('applyDetailedStatusConstraint applies today constraint', function () {
    Carbon::setTestNow('2026-05-29 12:00:00');

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('whereDate')->once()->with('start_date', Mockery::type('Illuminate\Support\Carbon'))->andReturnSelf();

    BookingListFilters::applyDetailedStatusConstraint($builder, BookingListFilters::STATUS_TODAY);

    Carbon::setTestNow();
});

test('applyDetailedStatusConstraint applies ongoing constraint', function () {
    Carbon::setTestNow('2026-05-29 12:00:00');

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->once()->with('start_date', '<=', Mockery::type('Illuminate\Support\Carbon'))->andReturnSelf();
    $builder->shouldReceive('where')->once()->with('end_date', '>=', Mockery::type('Illuminate\Support\Carbon'))->andReturnSelf();

    BookingListFilters::applyDetailedStatusConstraint($builder, BookingListFilters::STATUS_ONGOING);

    Carbon::setTestNow();
});

test('applyDetailedStatusConstraint applies incoming constraint', function () {
    Carbon::setTestNow('2026-05-29 12:00:00');

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')->once()->with('start_date', '>', Mockery::type('Illuminate\Support\Carbon'))->andReturnSelf();

    BookingListFilters::applyDetailedStatusConstraint($builder, BookingListFilters::STATUS_INCOMING);

    Carbon::setTestNow();
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

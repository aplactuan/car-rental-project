<?php

use App\Models\Driver;

it('builds a display name from first and last name', function () {
    expect(Driver::displayName('Jane', 'Doe'))->toBe('Jane Doe');
});

it('builds a display name from first name only when last name is missing', function () {
    expect(Driver::displayName('Jane', null))->toBe('Jane')
        ->and(Driver::displayName('Jane', ''))->toBe('Jane');
});

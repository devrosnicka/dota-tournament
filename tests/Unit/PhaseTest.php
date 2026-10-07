<?php

use App\Enums\Phase;

it('moves through the phases in the order of the spec', function () {
    $order = [];

    for ($phase = Phase::Registration; $phase !== null; $phase = $phase->next()) {
        $order[] = $phase->value;
    }

    expect($order)->toBe([
        'registration',
        'ranking',
        'schedule_review',
        'group_stage',
        'final_draft',
        'final',
        'finished',
    ]);
});

it('has a Czech label for every phase', function (Phase $phase) {
    expect($phase->label())->not->toBeEmpty();
})->with(Phase::cases());

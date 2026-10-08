<?php

use App\Domain\Final\FinalSeries;
use App\Domain\Final\MapResult;
use App\Enums\Side;

function series(Side ...$winners): FinalSeries
{
    return new FinalSeries(array_map(fn (Side $side) => new MapResult($side), $winners));
}

it('ends at two map wins and gives 3:0 points for 2:0', function () {
    $series = series(Side::A, Side::A);

    expect($series->winner())->toBe(Side::A)
        ->and($series->nextMap())->toBeNull()
        ->and($series->points(Side::A))->toBe(3)
        ->and($series->points(Side::B))->toBe(0);
});

it('plays a third map only at 1:1 and gives 3:1 points for 2:1', function () {
    expect(series(Side::A, Side::B)->nextMap())->toBe(3);

    $series = series(Side::A, Side::B, Side::B);

    expect($series->winner())->toBe(Side::B)
        ->and($series->points(Side::B))->toBe(3)
        ->and($series->points(Side::A))->toBe(1);
});

it('lets the side pick holder choose before map 1 and the loser before later maps', function () {
    expect(series()->chooser(Side::B))->toBe(Side::B)
        ->and(series()->nextMap())->toBe(1)
        ->and(series(Side::B)->chooser(Side::B))->toBe(Side::A)
        ->and(series(Side::B, Side::A)->chooser(Side::B))->toBe(Side::B)
        ->and(series(Side::A, Side::A)->chooser(Side::B))->toBeNull();
});

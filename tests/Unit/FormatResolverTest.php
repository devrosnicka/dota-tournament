<?php

use App\Domain\Schedule\FormatResolver;

it('maps the number of players to the round format', function (int $players, string $format, int $matches, int $sitting) {
    $round = (new FormatResolver)->resolve($players);

    expect($round->name())->toBe($format)
        ->and($round->matches)->toBe($matches)
        ->and($round->sitting())->toBe($sitting)
        ->and($round->playing() + $round->sitting())->toBe($players);
})->with([
    [10, '5v5', 1, 0],
    [11, '5v5', 1, 1],
    [12, '3v3', 2, 0],
    [13, '3v3', 2, 1],
    [14, '3v3', 2, 2],
    [15, '3v3', 2, 3],
    [16, '4v4', 2, 0],
]);

it('refuses player counts outside 10 to 16', function (int $players) {
    (new FormatResolver)->resolve($players);
})->with([0, 9, 17, 30])->throws(InvalidArgumentException::class);

<?php

namespace App\Domain\Schedule;

use InvalidArgumentException;

/**
 * Number of active players → format of the round (SPEC §1.3).
 */
final class FormatResolver
{
    public const MIN_PLAYERS = 10;

    public const MAX_PLAYERS = 16;

    public function resolve(int $players): RoundFormat
    {
        return match (true) {
            $players >= 10 && $players <= 11 => new RoundFormat($players, matches: 1, teamSize: 5),
            $players >= 12 && $players <= 15 => new RoundFormat($players, matches: 2, teamSize: 3),
            $players === 16 => new RoundFormat($players, matches: 2, teamSize: 4),
            default => throw new InvalidArgumentException(sprintf(
                'Rozpis lze vygenerovat jen pro %d až %d hráčů, aktivních je %d.',
                self::MIN_PLAYERS,
                self::MAX_PLAYERS,
                $players,
            )),
        };
    }
}

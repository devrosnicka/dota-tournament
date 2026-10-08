<?php

namespace App\Enums;

/**
 * What the 1st place captain chooses before the final draft (SPEC §1.10).
 * The other captain automatically gets the other one.
 */
enum Advantage: string
{
    case PlayerPick = 'player_pick';
    case SidePick = 'side_pick';

    public function other(): self
    {
        return $this === self::PlayerPick ? self::SidePick : self::PlayerPick;
    }

    public function label(): string
    {
        return match ($this) {
            self::PlayerPick => 'první výběr hráče',
            self::SidePick => 'strana a první pick na 1. mapě',
        };
    }
}

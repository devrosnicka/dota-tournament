<?php

namespace App\Domain\Schedule;

/**
 * Shape of one group stage round for a given number of active players.
 */
final readonly class RoundFormat
{
    public function __construct(
        public int $players,
        public int $matches,
        public int $teamSize,
    ) {}

    public function name(): string
    {
        return "{$this->teamSize}v{$this->teamSize}";
    }

    public function playing(): int
    {
        return $this->matches * $this->teamSize * 2;
    }

    public function sitting(): int
    {
        return $this->players - $this->playing();
    }
}

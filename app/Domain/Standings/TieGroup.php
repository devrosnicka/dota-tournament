<?php

namespace App\Domain\Standings;

/**
 * Players level on points and kill difference across the qualification line.
 */
final readonly class TieGroup
{
    /**
     * @param  list<int>  $players  in provisional (seed) order
     * @param  int  $spots  how many of them qualify
     */
    public function __construct(
        public array $players,
        public int $spots,
        public bool $resolved,
    ) {}
}

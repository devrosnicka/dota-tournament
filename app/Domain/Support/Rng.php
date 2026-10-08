<?php

namespace App\Domain\Support;

use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Seeded randomness, so that a generated schedule can be reproduced from
 * its stored seed (SPEC §1.6).
 */
final class Rng
{
    private readonly Randomizer $randomizer;

    public function __construct(public readonly int $seed)
    {
        $this->randomizer = new Randomizer(new Mt19937($seed));
    }

    /**
     * A new seed derived from this one, e.g. for one restart of a search.
     */
    public static function derive(int $seed, string $salt): int
    {
        return (int) hexdec(substr(hash('sha256', "{$seed}|{$salt}"), 0, 12));
    }

    /**
     * @template T
     *
     * @param  list<T>  $items
     * @return list<T>
     */
    public function shuffle(array $items): array
    {
        return $this->randomizer->shuffleArray($items);
    }

    public function bool(): bool
    {
        return $this->randomizer->getInt(0, 1) === 1;
    }
}

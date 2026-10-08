<?php

namespace App\Domain\Support;

/**
 * Deterministic draw: the order is derived from a stored random seed, so it
 * stays the same on every page load (SPEC §1.9, PLAN §3).
 */
final readonly class Lottery
{
    public function __construct(private int $seed) {}

    public function key(string $context, int $id): string
    {
        return hash('sha256', "{$this->seed}|{$context}|{$id}");
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    public function order(string $context, array $ids): array
    {
        usort($ids, fn (int $a, int $b) => strcmp($this->key($context, $a), $this->key($context, $b)));

        return $ids;
    }
}

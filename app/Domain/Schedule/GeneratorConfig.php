<?php

namespace App\Domain\Schedule;

final readonly class GeneratorConfig
{
    public function __construct(
        public float $teammateRepeat = 10,
        public float $opponentRepeat = 3,
        public float $topSeedsTogether = 50,
        public float $seedImbalance = 20,
        public float $seedTolerance = 2.0,
        public int $samplesPerRound = 5000,
        public int $restarts = 20,
    ) {}

    /**
     * @param  array{weights?: array<string, int|float>, seed_tolerance?: int|float, samples_per_round?: int, restarts?: int}  $config
     */
    public static function fromArray(array $config): self
    {
        $weights = $config['weights'] ?? [];

        return new self(
            teammateRepeat: (float) ($weights['teammate_repeat'] ?? 10),
            opponentRepeat: (float) ($weights['opponent_repeat'] ?? 3),
            topSeedsTogether: (float) ($weights['top_seeds_together'] ?? 50),
            seedImbalance: (float) ($weights['seed_imbalance'] ?? 20),
            seedTolerance: (float) ($config['seed_tolerance'] ?? 2.0),
            samplesPerRound: (int) ($config['samples_per_round'] ?? 5000),
            restarts: (int) ($config['restarts'] ?? 20),
        );
    }
}

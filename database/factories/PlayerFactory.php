<?php

namespace Database\Factories;

use App\Enums\PlayerStatus;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nick' => fake()->unique()->firstName(),
        ];
    }

    public function withdrawn(int $fromRound): static
    {
        return $this->state([
            'status' => PlayerStatus::Withdrawn,
            'withdrawn_from_round' => $fromRound,
        ]);
    }
}

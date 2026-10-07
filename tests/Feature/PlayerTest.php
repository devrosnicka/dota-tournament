<?php

use App\Enums\PlayerStatus;
use App\Models\Player;
use Illuminate\Database\UniqueConstraintViolationException;

it('is active by default', function () {
    $player = Player::factory()->create()->fresh();

    expect($player->status)->toBe(PlayerStatus::Active);
});

it('treats nicks as case-insensitively unique', function () {
    Player::factory()->create(['nick' => 'Rosnicka']);

    Player::factory()->create(['nick' => 'rosnicka']);
})->throws(UniqueConstraintViolationException::class);

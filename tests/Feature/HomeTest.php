<?php

use App\Enums\Phase;
use App\Models\Player;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the player home page with the current phase', function () {
    $player = Player::factory()->create(['nick' => 'Puck']);

    $this->actingAs($player)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('home')
            ->where('name', config('app.name'))
            ->where('phase.value', 'registration')
            ->where('phase.label', 'Registrace')
            ->where('auth.player.nick', 'Puck')
            ->where('auth.isAdmin', false));
});

it('shares a phase change on the next request', function () {
    setPhase(Phase::GroupStage);

    $this->actingAs(Player::factory()->create())
        ->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->where('phase.value', 'group_stage')
            ->where('phase.label', 'Základní část'));
});

it('exposes a health check', function () {
    $this->get('/up')->assertOk();
});

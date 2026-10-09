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

it('shows players the rules with the numbers of this tournament', function () {
    $players = Player::factory()->count(13)->create();

    $this->actingAs($players[0])
        ->get('/rules')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rules')
            ->where('rounds', 5)
            ->where('players', 13)
            ->where('format', ['name' => '3v3', 'matches' => 2, 'sitting' => 1])
            ->where('finalists', 10));
});

it('keeps the rules from guests', function () {
    $this->get('/rules')->assertRedirect('/register');
});

it('shows guests the tournament flow', function () {
    $this->get('/how-it-works')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('how-it-works')
            ->where('phase.value', 'registration')
            ->where('players', 0)
            ->where('format', null)
            ->where('rounds', 5)
            ->where('finalists', 10)
            ->where('registrationOpen', true));

    Player::factory()->count(13)->create();
    setPhase(Phase::GroupStage);

    $this->get('/how-it-works')
        ->assertInertia(fn (Assert $page) => $page
            ->where('players', 13)
            ->where('format', ['name' => '3v3', 'matches' => 2, 'sitting' => 1])
            ->where('registrationOpen', false));
});

it('sends players from the tournament flow to the full rules', function () {
    $this->actingAs(Player::factory()->create())
        ->get('/how-it-works')
        ->assertRedirect('/rules');
});

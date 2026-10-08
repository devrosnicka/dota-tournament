<?php

use App\Enums\Phase;
use App\Models\Player;
use App\Tournament\Rankings;
use Inertia\Testing\AssertableInertia as Assert;

it('needs the TV key', function () {
    $this->get('/tv')->assertNotFound();
    $this->get('/tv?key=wrong')->assertNotFound();
    $this->get('/tv?key=tv-key')->assertOk();
});

it('is closed when no TV key is configured', function () {
    config(['tournament.tv_key' => '']);

    $this->get('/tv?key=')->assertNotFound();
});

it('shows registered players during registration', function () {
    Player::factory()->create(['nick' => 'Axe']);
    Player::factory()->create(['nick' => 'Bane']);

    $this->get('/tv?key=tv-key')->assertInertia(fn (Assert $page) => $page
        ->component('tv/index')
        ->where('players', ['Axe', 'Bane'])
        ->where('auth.player', null));
});

it('shows only how many players submitted their ranking', function () {
    $players = Player::factory()->count(4)->create();
    setPhase(Phase::Ranking);
    app(Rankings::class)->save($players[0], $players->skip(1)->pluck('id')->values()->all());

    $this->get('/tv?key=tv-key')->assertInertia(fn (Assert $page) => $page
        ->where('ranking', ['submitted' => 1, 'total' => 4])
        ->missing('players'));
});

it('shows the current round and the table during the group stage', function () {
    seededTournament(12, 3);
    setPhase(Phase::GroupStage);
    playRounds([1]);

    $this->get('/tv?key=tv-key')->assertInertia(fn (Assert $page) => $page
        ->where('round.number', 2)
        ->has('standings.rows', 12)
        ->missing('round.matches.0.teamA.0.seed'));
});

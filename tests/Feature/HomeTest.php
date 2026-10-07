<?php

use App\Enums\Phase;
use App\Tournament\TournamentSettings;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the home page with the current phase', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('home')
            ->where('name', config('app.name'))
            ->where('phase.value', 'registration')
            ->where('phase.label', 'Registrace'));
});

it('shares a phase change on the next request', function () {
    app(TournamentSettings::class)->setPhase(Phase::GroupStage);

    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->where('phase.value', 'group_stage')
            ->where('phase.label', 'Základní část'));
});

it('exposes a health check', function () {
    $this->get('/up')->assertOk();
});

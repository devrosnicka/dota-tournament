<?php

use App\Enums\Phase;
use App\Models\Setting;
use App\Tournament\TournamentSettings;

it('starts in the registration phase', function () {
    expect(app(TournamentSettings::class)->phase())->toBe(Phase::Registration);
});

it('persists values as JSON', function () {
    $settings = app(TournamentSettings::class);

    $settings->set('rounds', 5);
    $settings->set('weights', ['teammate_repeat' => 10]);
    $settings->setPhase(Phase::Ranking);

    $fresh = new TournamentSettings;

    expect($fresh->get('rounds'))->toBe(5)
        ->and($fresh->get('weights'))->toBe(['teammate_repeat' => 10])
        ->and($fresh->get('missing', 'default'))->toBe('default')
        ->and($fresh->phase())->toBe(Phase::Ranking)
        ->and(Setting::query()->count())->toBe(3);
});

it('keeps its cached values in sync after a write', function () {
    $settings = app(TournamentSettings::class);

    expect($settings->phase())->toBe(Phase::Registration);

    $settings->setPhase(Phase::Final);

    expect($settings->phase())->toBe(Phase::Final);
});

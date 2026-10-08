<?php

use App\Enums\Phase;
use App\Models\AuditLog;
use App\Tournament\TournamentSettings;
use Inertia\Testing\AssertableInertia as Assert;

it('closes registration by moving to ranking', function () {
    asAdmin()->post('/admin/phase/advance')->assertRedirect('/admin');

    expect(app(TournamentSettings::class)->phase())->toBe(Phase::Ranking)
        ->and(AuditLog::query()->where('action', 'phase.advanced')->value('payload'))
        ->toBe(['from' => 'registration', 'to' => 'ranking']);
});

it('can reopen registration from ranking', function () {
    setPhase(Phase::Ranking);

    asAdmin()->post('/admin/phase/revert')->assertRedirect('/admin');

    expect(app(TournamentSettings::class)->phase())->toBe(Phase::Registration);
});

it('cannot go back once the group stage has started', function () {
    setPhase(Phase::GroupStage);

    asAdmin()->post('/admin/phase/revert');

    expect(app(TournamentSettings::class)->phase())->toBe(Phase::GroupStage);
});

it('shows the next phase and what blocks it on the dashboard', function () {
    asAdmin()->get('/admin')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->has('phases', 7)
            ->where('next.value', 'ranking')
            ->where('blockers', [])
            ->where('revertTo', null));
});

it('is driven by the admin only', function () {
    $this->post('/admin/phase/advance')->assertRedirect('/admin/login');

    expect(app(TournamentSettings::class)->phase())->toBe(Phase::Registration);
});

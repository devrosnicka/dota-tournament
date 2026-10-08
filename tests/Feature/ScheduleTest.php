<?php

use App\Enums\Phase;
use App\Enums\Side;
use App\Models\AuditLog;
use App\Models\Player;
use App\Models\Round;
use App\Tournament\Schedule;
use App\Tournament\TournamentSettings;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

it('generates the whole schedule from the seeding', function (int $count, string $format) {
    $players = Player::factory()->count($count)->create();
    $players->each(fn (Player $p, int $i) => $p->forceFill(['seed_rank' => $i + 1])->save());
    setPhase(Phase::ScheduleReview);

    asAdmin()->post('/admin/schedule/generate', ['rounds' => 4])->assertRedirect('/admin/schedule');

    $rounds = app(Schedule::class)->rounds();

    expect($rounds)->toHaveCount(4)
        ->and($rounds->pluck('number')->all())->toBe([1, 2, 3, 4])
        ->and(app(TournamentSettings::class)->get('rounds'))->toBe(4)
        ->and(app(TournamentSettings::class)->get('schedule_seed'))->toBeInt()
        ->and(AuditLog::query()->where('action', 'schedule.generated')->exists())->toBeTrue();

    foreach ($rounds as $round) {
        $everyone = [...$round->sitters->pluck('id'), ...$round->matches->flatMap->players->pluck('id')];
        sort($everyone);

        expect($round->format)->toBe($format)
            ->and($everyone)->toBe($players->pluck('id')->sort()->values()->all());
    }
})->with([[10, '5v5'], [13, '3v3'], [16, '4v4']]);

it('replaces the schedule when generated again', function () {
    seededTournament(12, 5);

    asAdmin()->post('/admin/schedule/generate', ['rounds' => 3]);

    expect(Round::query()->count())->toBe(3);
});

it('generates only while the schedule is being prepared', function () {
    seededTournament(12, 5);
    setPhase(Phase::GroupStage);

    asAdmin()->post('/admin/schedule/generate', ['rounds' => 3])->assertSessionHasErrors('rounds');

    expect(Round::query()->count())->toBe(5);
});

it('swaps two players between teams', function () {
    seededTournament(12, 2);
    $round = Round::query()->where('number', 1)->with('matches.players')->first();
    $match = $round->matches->first();
    [$a] = $match->team(Side::A);
    [$b] = $match->team(Side::B);

    asAdmin()->post("/admin/schedule/rounds/{$round->id}/swap", ['first' => $a, 'second' => $b])
        ->assertRedirect('/admin/schedule');

    $match->load('players');
    expect($match->team(Side::A))->toContain($b)->not->toContain($a)
        ->and($match->team(Side::B))->toContain($a);
});

it('swaps a playing player with a sitting one and warns about the sit rules', function () {
    seededTournament(13, 2);
    $first = Round::query()->where('number', 1)->with(['matches.players', 'sitters'])->first();
    $second = Round::query()->where('number', 2)->with(['matches.players', 'sitters'])->first();
    $sitter = $first->sitters->first()->id;

    // Put the round 1 sitter on the bench in round 2 as well.
    $secondSitter = $second->sitters->first()->id;
    $playing = $second->matches->first()->team(Side::A)[0];
    asAdmin()->post("/admin/schedule/rounds/{$second->id}/swap", ['first' => $secondSitter, 'second' => $playing]);
    asAdmin()->post("/admin/schedule/rounds/{$second->id}/swap", ['first' => $playing, 'second' => $sitter])->assertRedirect();

    expect(DB::table('round_sits')->where('round_id', $second->id)->pluck('player_id')->all())->toBe([$sitter]);

    asAdmin()->get('/admin/schedule')->assertInertia(fn (Assert $page) => $page
        ->where('analysis.warnings', fn ($warnings) => collect($warnings)->contains(fn ($w) => str_contains($w, 'sedí v 1. i 2. kole'))
            && collect($warnings)->contains('Po 2. kole se počty sezení liší o víc než 1.')));
});

it('refuses to swap a player with themselves or from another round', function () {
    $players = seededTournament(12, 2);

    $round = Round::query()->where('number', 1)->first();

    asAdmin()->post("/admin/schedule/rounds/{$round->id}/swap", ['first' => $players[0]->id, 'second' => $players[0]->id])
        ->assertSessionHasErrors('players');
    asAdmin()->post("/admin/schedule/rounds/{$round->id}/swap", ['first' => $players[0]->id, 'second' => 999])
        ->assertSessionHasErrors('players');
});

it('needs a schedule before the group stage can start', function () {
    Player::factory()->count(12)->create();
    setPhase(Phase::ScheduleReview);

    asAdmin()->post('/admin/phase/advance');
    expect(app(TournamentSettings::class)->phase())->toBe(Phase::ScheduleReview);

    app(Schedule::class)->generate(3);
    asAdmin()->post('/admin/phase/advance');
    expect(app(TournamentSettings::class)->phase())->toBe(Phase::GroupStage);
});

it('shows players the schedule only once it is published', function () {
    $players = seededTournament(12, 3);
    $me = $players->first();

    $this->actingAs($me)->get('/schedule')
        ->assertInertia(fn (Assert $page) => $page->component('schedule')->where('rounds', null));

    setPhase(Phase::GroupStage);

    $this->actingAs($me)->get('/schedule')
        ->assertInertia(fn (Assert $page) => $page
            ->has('rounds', 3)
            ->where('me', $me->id)
            ->missing('rounds.0.matches.0.teamA.0.seed'));
});

it('deletes the schedule when going back to ranking', function () {
    seededTournament(12, 3);

    asAdmin()->post('/admin/phase/revert');

    expect(Round::query()->count())->toBe(0)
        ->and(DB::table('matches')->count())->toBe(0)
        ->and(Player::query()->whereNotNull('seed_rank')->count())->toBe(0);
});

it('shows the admin a preview with statistics', function () {
    seededTournament(14, 4);

    asAdmin()->get('/admin/schedule')->assertInertia(fn (Assert $page) => $page
        ->component('admin/schedule')
        ->has('rounds', 4)
        ->where('editable', true)
        ->has('rounds.0.matches.0.teamA.0.seed')
        ->has('analysis.sits', 14)
        ->where('analysis.warnings', []));
});

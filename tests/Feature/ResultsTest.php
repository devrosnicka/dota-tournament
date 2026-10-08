<?php

use App\Enums\Phase;
use App\Enums\RoundStatus;
use App\Enums\Side;
use App\Models\AuditLog;
use App\Models\Player;
use App\Models\Round;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->players = seededTournament(12, 3);
    setPhase(Phase::GroupStage);
    $this->round = Round::query()->where('number', 1)->with('matches.players')->first();
    $this->match = $this->round->matches->first();
    $this->player = Player::find($this->match->team(Side::A)[0]);
});

it('lets a player of the match report the result', function () {
    $this->actingAs($this->player)
        ->post("/matches/{$this->match->id}/result", ['winner' => 'B', 'kills_a' => 12, 'kills_b' => 30])
        ->assertRedirect("/matches/{$this->match->id}");

    $match = $this->match->fresh();

    expect($match->winner)->toBe(Side::B)
        ->and([$match->kills_a, $match->kills_b])->toBe([12, 30])
        ->and($match->reported_by)->toBe($this->player->id)
        ->and($this->round->fresh()->status)->toBe(RoundStatus::InProgress)
        ->and(AuditLog::query()->where('action', 'match.reported')->value('player_id'))->toBe($this->player->id);
});

it('does not let other players report', function () {
    $outsider = Player::query()->whereNotIn('id', $this->match->players->pluck('id'))->first();

    $this->actingAs($outsider)
        ->post("/matches/{$this->match->id}/result", ['winner' => 'A', 'kills_a' => 1, 'kills_b' => 0])
        ->assertForbidden();

    expect($this->match->fresh()->winner)->toBeNull();
});

it('validates the result', function (array $data, string $field) {
    $this->actingAs($this->player)
        ->post("/matches/{$this->match->id}/result", $data)
        ->assertSessionHasErrors($field);
})->with([
    [['kills_a' => 1, 'kills_b' => 2], 'winner'],
    [['winner' => 'C', 'kills_a' => 1, 'kills_b' => 2], 'winner'],
    [['winner' => 'A', 'kills_a' => -1, 'kills_b' => 2], 'kills_a'],
    [['winner' => 'A', 'kills_a' => 1], 'kills_b'],
]);

it('allows overwriting until the round is closed', function () {
    $url = "/matches/{$this->match->id}/result";

    $this->actingAs($this->player)->post($url, ['winner' => 'A', 'kills_a' => 10, 'kills_b' => 5]);
    $this->actingAs($this->player)->post($url, ['winner' => 'B', 'kills_a' => 10, 'kills_b' => 15]);
    expect($this->match->fresh()->winner)->toBe(Side::B);

    playRounds([1]);

    $this->actingAs($this->player)->post($url, ['winner' => 'A', 'kills_a' => 1, 'kills_b' => 0])
        ->assertSessionHasErrors('winner');
});

it('lets the admin correct any result, even in a closed round', function () {
    playRounds([1]);

    asAdmin()->post("/admin/matches/{$this->match->id}/result", ['winner' => 'B', 'kills_a' => 3, 'kills_b' => 4])
        ->assertRedirect('/admin/results');

    expect($this->match->fresh()->winner)->toBe(Side::B)
        ->and($this->match->fresh()->reported_by)->toBeNull();
});

it('closes a round only once every match has a result', function () {
    asAdmin()->post("/admin/rounds/{$this->round->id}/close")->assertSessionHasErrors('round');
    expect($this->round->fresh()->status)->toBe(RoundStatus::Planned);

    foreach ($this->round->matches as $match) {
        asAdmin()->post("/admin/matches/{$match->id}/result", ['winner' => 'A', 'kills_a' => 3, 'kills_b' => 1]);
    }

    asAdmin()->post("/admin/rounds/{$this->round->id}/close");
    expect($this->round->fresh()->status)->toBe(RoundStatus::Done);
});

it('reopens only the last closed round', function () {
    playRounds([1, 2]);
    $first = Round::query()->where('number', 1)->first();
    $second = Round::query()->where('number', 2)->first();

    asAdmin()->post("/admin/rounds/{$first->id}/reopen")->assertSessionHasErrors('round');
    asAdmin()->post("/admin/rounds/{$second->id}/reopen");

    expect($first->fresh()->status)->toBe(RoundStatus::Done)
        ->and($second->fresh()->status)->toBe(RoundStatus::InProgress);
});

it('shows the player their current match on the home page', function () {
    $this->actingAs($this->player)->get('/')->assertInertia(fn (Assert $page) => $page
        ->where('groupStage.round', 1)
        ->where('groupStage.match.id', $this->match->id)
        ->where('groupStage.match.side', 'A')
        ->where('groupStage.sitting', false));
});

it('shows the match page with the report form only to its players', function () {
    $this->actingAs($this->player)->get("/matches/{$this->match->id}")
        ->assertInertia(fn (Assert $page) => $page->component('match')->where('canReport', true));

    $outsider = Player::query()->whereNotIn('id', $this->match->players->pluck('id'))->first();
    $this->actingAs($outsider)->get("/matches/{$this->match->id}")
        ->assertInertia(fn (Assert $page) => $page->where('canReport', false));
});

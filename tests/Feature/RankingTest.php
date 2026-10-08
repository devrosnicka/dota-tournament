<?php

use App\Enums\Phase;
use App\Models\AuditLog;
use App\Models\Player;
use App\Tournament\Rankings;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->players = Player::factory()->count(5)->create();
    $this->rater = $this->players->first();
    $this->others = $this->players->skip(1)->pluck('id')->values()->all();
    setPhase(Phase::Ranking);
});

it('saves the order of all other players', function () {
    $order = array_reverse($this->others);

    $this->actingAs($this->rater)
        ->post('/ranking', ['order' => $order])
        ->assertRedirect('/ranking');

    expect(app(Rankings::class)->savedOrder($this->rater))->toBe($order)
        ->and(DB::table('rankings')->where('rater_id', $this->rater->id)->orderBy('position')->pluck('ratee_id')->all())->toBe($order);
});

it('replaces a previous order', function () {
    $this->actingAs($this->rater)->post('/ranking', ['order' => $this->others]);
    $this->actingAs($this->rater)->post('/ranking', ['order' => array_reverse($this->others)]);

    expect(app(Rankings::class)->savedOrder($this->rater))->toBe(array_reverse($this->others))
        ->and(DB::table('rankings')->count())->toBe(4);
});

it('rejects an order that is not exactly the other players', function (string $case) {
    $others = $this->others;
    $order = match ($case) {
        'missing a player' => array_slice($others, 1),
        'including themselves' => [...$others, $this->rater->id],
        'duplicates' => [...array_slice($others, 1), $others[1]],
        'unknown player' => [...array_slice($others, 1), 999],
    };

    $this->actingAs($this->rater)
        ->post('/ranking', ['order' => $order])
        ->assertSessionHasErrors('order');

    expect(DB::table('rankings')->count())->toBe(0);
})->with(['missing a player', 'including themselves', 'duplicates', 'unknown player']);

it('is closed outside the ranking phase', function () {
    setPhase(Phase::ScheduleReview);

    $this->actingAs($this->rater)
        ->post('/ranking', ['order' => $this->others])
        ->assertSessionHasErrors('order');

    expect(DB::table('rankings')->count())->toBe(0);
});

it('does not write the order into the audit log', function () {
    $this->actingAs($this->rater)->post('/ranking', ['order' => $this->others]);

    $entry = AuditLog::query()->where('action', 'ranking.saved')->sole();

    expect($entry->player_id)->toBe($this->rater->id)
        ->and($entry->payload)->toBeNull();
});

it('shows the player their own saved order', function () {
    $order = array_reverse($this->others);
    $this->actingAs($this->rater)->post('/ranking', ['order' => $order]);

    $this->actingAs($this->rater)
        ->get('/ranking')
        ->assertInertia(fn (Assert $page) => $page
            ->component('ranking')
            ->where('players', fn ($players) => collect($players)->pluck('id')->all() === $order)
            ->where('status', 'saved')
            ->where('editable', true));
});

it('asks to save again when a player was added', function () {
    $this->actingAs($this->rater)->post('/ranking', ['order' => $this->others]);
    $newcomer = Player::factory()->create();

    $this->actingAs($this->rater)
        ->get('/ranking')
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'outdated')
            ->where('players.4.id', $newcomer->id));
});

it('shows a stable starting order before the first save', function () {
    $first = $this->actingAs($this->rater)->get('/ranking')->inertiaProps('players');
    $second = $this->actingAs($this->rater)->get('/ranking')->inertiaProps('players');

    expect($first)->toBe($second)
        ->and(collect($first)->pluck('id')->sort()->values()->all())->toBe($this->others);
});

it('is read-only after the ranking phase', function () {
    setPhase(Phase::GroupStage);

    $this->actingAs($this->rater)
        ->get('/ranking')
        ->assertInertia(fn (Assert $page) => $page->where('editable', false));
});

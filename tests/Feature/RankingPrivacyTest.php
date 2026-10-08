<?php

use App\Domain\Support\Rng;
use App\Enums\Phase;
use App\Models\Player;
use App\Tournament\Rankings;
use App\Tournament\Schedule;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/*
| SPEC §2.2: raw rankings never leave the server except the logged-in
| player's own order, and the seeding is visible to the admin only.
*/

beforeEach(function () {
    // Nicks sort like ids, so alphabetical lists follow ascending ids.
    $this->players = collect(range(1, 10))->map(fn (int $i) => Player::factory()->create([
        'nick' => sprintf('Hrac%02d', $i),
    ]));
    $this->orders = [];

    setPhase(Phase::Ranking);

    // Each rater submits a random order; the last player does not submit.
    $rng = new Rng(2026);

    foreach ($this->players->take(9) as $rater) {
        $order = $rng->shuffle($this->players->pluck('id')->reject(fn (int $id) => $id === $rater->id)->values()->all());

        app(Rankings::class)->save($rater, $order);
        $this->orders[$rater->id] = $order;
    }
});

/**
 * @return list<string>
 */
function privacyUrls(): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route) => in_array('GET', $route->methods(), true)
            && str_starts_with($route->getActionName(), 'App\\'))
        ->map(fn (RoutingRoute $route) => '/'.ltrim(preg_replace('/\{[^}]+\}/', '1', $route->uri()), '/'))
        ->values()
        ->all();
}

/**
 * Whether the page lists players in exactly the given order.
 *
 * @param  list<int>  $order
 */
function containsRun(string $body, array $order): bool
{
    $pattern = implode(',[^{}]*\\},\\{', array_map(fn (int $id) => "\"id\":{$id}", $order));

    return preg_match("/{$pattern}\\b/", $body) === 1;
}

function assertNothingLeaks(TestCase $test, ?Player $viewer): void
{
    foreach (privacyUrls() as $url) {
        $body = html_entity_decode($test->get($url)->getContent() ?: '');

        foreach (['seed', 'seeding', 'seedRank', 'seed_rank', 'seedScore', 'seed_score', 'simpleMean', 'rankings'] as $key) {
            expect($body)->not->toContain("\"{$key}\"", "{$url} exposes {$key}");
        }

        foreach ($test->orders as $raterId => $order) {
            if ($viewer?->id !== $raterId) {
                expect(containsRun($body, $order))->toBeFalse("{$url} exposes the ranking of player {$raterId}");
            }
        }
    }
}

it('shows a player only their own ranking', function (Phase $phase) {
    if ($phase !== Phase::Ranking) {
        app(Rankings::class)->snapshot();
        setPhase(Phase::ScheduleReview);
        app(Schedule::class)->generate(3);
    }

    setPhase($phase);

    $viewer = $this->players->first();
    $this->actingAs($viewer);

    assertNothingLeaks($this, $viewer);

    // The check above must be able to see a ranking: the viewer's own one.
    $own = html_entity_decode($this->get('/ranking')->getContent());
    expect(containsRun($own, $this->orders[$viewer->id]))->toBeTrue();
})->with([Phase::Ranking, Phase::ScheduleReview, Phase::GroupStage, Phase::Finished]);

it('shows guests nothing', function () {
    assertNothingLeaks($this, null);
});

it('keeps rankings and the seeding off the TV', function (Phase $phase) {
    if ($phase !== Phase::Ranking) {
        app(Rankings::class)->snapshot();
        setPhase(Phase::ScheduleReview);
        app(Schedule::class)->generate(3);
    }

    setPhase($phase);
    $body = html_entity_decode($this->get('/tv?key=tv-key')->assertOk()->getContent());

    foreach (['seed', 'seedRank', 'seed_rank', 'seed_score', 'rankings'] as $key) {
        expect($body)->not->toContain("\"{$key}\"");
    }

    foreach ($this->orders as $order) {
        expect(containsRun($body, $order))->toBeFalse();
    }
})->with([Phase::Ranking, Phase::GroupStage]);

it('lets the admin see the seeding', function () {
    asAdmin()->get('/admin/seeding')->assertOk()->assertSee('seeding');
});

it('keeps the seeding page from players', function () {
    $this->actingAs($this->players->first())->get('/admin/seeding')->assertRedirect('/admin/login');
});

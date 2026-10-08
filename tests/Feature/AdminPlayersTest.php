<?php

use App\Enums\Phase;
use App\Models\AuditLog;
use App\Models\Player;
use Inertia\Testing\AssertableInertia as Assert;

it('lists players with their active login codes', function () {
    $withCode = Player::factory()->create(['nick' => 'Axe']);
    Player::factory()->create(['nick' => 'Bane']);

    asAdmin()->post("/admin/players/{$withCode->id}/login-code")->assertRedirect('/admin/players');

    asAdmin()->get('/admin/players')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/players')
            ->has('players', 2)
            ->where('players.0.nick', 'Axe')
            ->where('players.0.loginCode.code', $withCode->fresh()->login_code)
            ->where('players.1.loginCode', null)
            ->where('canDelete', true));
});

it('renames a player', function () {
    $player = Player::factory()->create(['nick' => 'Axe']);

    asAdmin()->patch("/admin/players/{$player->id}", ['nick' => 'Axe2'])->assertRedirect('/admin/players');

    expect($player->fresh()->nick)->toBe('Axe2')
        ->and(AuditLog::query()->where('action', 'player.renamed')->value('payload'))
        ->toBe(['player_id' => $player->id, 'from' => 'Axe', 'to' => 'Axe2']);
});

it('does not rename to a taken nick', function () {
    Player::factory()->create(['nick' => 'Bane']);
    $player = Player::factory()->create(['nick' => 'Axe']);

    asAdmin()->patch("/admin/players/{$player->id}", ['nick' => 'bane'])->assertSessionHasErrors('nick');

    expect($player->fresh()->nick)->toBe('Axe');
});

it('deletes a player only before the schedule exists', function () {
    $player = Player::factory()->create();

    setPhase(Phase::ScheduleReview);
    asAdmin()->delete("/admin/players/{$player->id}");
    expect(Player::query()->count())->toBe(1);

    setPhase(Phase::Ranking);
    asAdmin()->delete("/admin/players/{$player->id}");
    expect(Player::query()->count())->toBe(0);
});

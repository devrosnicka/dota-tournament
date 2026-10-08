<?php

use App\Enums\Phase;
use App\Models\AuditLog;
use App\Models\Player;

it('registers a player and logs them in', function () {
    $this->post('/register', ['nick' => 'Puck', 'registration_code' => 'lan-party'])
        ->assertRedirect('/');

    $player = Player::query()->sole();

    expect($player->nick)->toBe('Puck');
    $this->assertAuthenticatedAs($player);
    expect(AuditLog::query()->where('action', 'player.registered')->exists())->toBeTrue();
});

it('fails without the registration code', function () {
    $this->post('/register', ['nick' => 'Puck'])
        ->assertSessionHasErrors('registration_code');

    expect(Player::query()->count())->toBe(0);
    $this->assertGuest();
});

it('fails with a wrong registration code', function () {
    $this->post('/register', ['nick' => 'Puck', 'registration_code' => 'guess'])
        ->assertSessionHasErrors('registration_code');

    expect(Player::query()->count())->toBe(0);
});

it('is closed when no registration code is configured', function () {
    config(['tournament.registration_code' => null]);

    $this->post('/register', ['nick' => 'Puck', 'registration_code' => ''])
        ->assertSessionHasErrors('registration_code');

    expect(Player::query()->count())->toBe(0);
});

it('fails outside the registration phase', function (Phase $phase) {
    setPhase($phase);

    $this->post('/register', ['nick' => 'Puck', 'registration_code' => 'lan-party'])
        ->assertSessionHasErrors('nick');

    expect(Player::query()->count())->toBe(0);
})->with([Phase::Ranking, Phase::GroupStage, Phase::Finished]);

it('rejects a nick that differs only in letter case', function () {
    Player::factory()->create(['nick' => 'Puck']);

    $this->post('/register', ['nick' => 'puck', 'registration_code' => 'lan-party'])
        ->assertSessionHasErrors('nick');

    expect(Player::query()->count())->toBe(1);
});

it('shows whether registration is open', function () {
    $this->get('/register')->assertInertia(fn ($page) => $page->component('auth/register')->where('open', true));

    setPhase(Phase::Ranking);

    $this->get('/register')->assertInertia(fn ($page) => $page->where('open', false));
});

it('sends a logged-in player home', function () {
    $this->actingAs(Player::factory()->create())
        ->get('/register')
        ->assertRedirect('/');
});

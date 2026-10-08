<?php

use App\Models\Player;
use App\Tournament\LoginCodes;

it('lets a logged-in player issue a code for another device', function () {
    $player = Player::factory()->create();

    $this->actingAs($player)
        ->post('/device')
        ->assertRedirect('/device')
        ->assertSessionHas('device_login_code.code', fn (string $code) => $code === $player->fresh()->login_code);

    expect($player->fresh()->login_code)->toMatch('/^\d{4}$/');
});

it('logs in with a valid code exactly once', function () {
    $player = Player::factory()->create();
    $code = app(LoginCodes::class)->issue($player);

    $this->post('/login', ['code' => $code])->assertRedirect('/');
    $this->assertAuthenticatedAs($player);

    auth()->logout();

    $this->post('/login', ['code' => $code])->assertSessionHasErrors('code');
    $this->assertGuest();
});

it('rejects an expired code', function () {
    $player = Player::factory()->create();
    $code = app(LoginCodes::class)->issue($player);

    $this->travel(LoginCodes::TTL_MINUTES + 1)->minutes();

    $this->post('/login', ['code' => $code])->assertSessionHasErrors('code');
    $this->assertGuest();
});

it('rejects a wrong code', function () {
    $player = Player::factory()->create();
    $code = app(LoginCodes::class)->issue($player);
    $wrong = str_pad((string) (((int) $code + 1) % 10000), 4, '0', STR_PAD_LEFT);

    $this->post('/login', ['code' => $wrong])->assertSessionHasErrors('code');
    $this->assertGuest();
});

it('keeps active codes unique', function () {
    $players = Player::factory()->count(30)->create();
    $codes = $players->map(fn (Player $player) => app(LoginCodes::class)->issue($player));

    expect($codes->unique())->toHaveCount(30);
});

it('rate limits login attempts', function () {
    $player = Player::factory()->create();
    $code = app(LoginCodes::class)->issue($player);

    foreach (range(1, 10) as $attempt) {
        $this->post('/login', ['code' => $code === '0000' ? '1111' : '0000']);
    }

    $this->post('/login', ['code' => $code])
        ->assertSessionHasErrors(['code' => 'Příliš mnoho pokusů. Zkus to znovu za 60 s.']);
    $this->assertGuest();
});

it('logs out only the player, not the admin', function () {
    asAdmin()->actingAs(Player::factory()->create())
        ->post('/logout')
        ->assertRedirect('/login');

    $this->assertGuest();
    $this->get('/admin')->assertOk();
});

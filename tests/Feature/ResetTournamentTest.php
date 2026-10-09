<?php

use App\Enums\Phase;
use App\Models\AuditLog;
use App\Models\Player;
use App\Tournament\TournamentReset;
use App\Tournament\TournamentSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

it('lets the admin delete the whole tournament and reopen registration', function () {
    startFinal();
    completeDraft();

    asAdmin()->post('/admin/reset')->assertRedirect('/admin');

    foreach (TournamentReset::TABLES as $table) {
        $expected = $table === 'audit_log' ? 1 : 0;
        expect(DB::table($table)->count())->toBe($expected, "Table {$table} is not empty");
    }

    // The settings of this test were loaded before the reset.
    app()->forgetScopedInstances();

    expect(app(TournamentSettings::class)->phase())->toBe(Phase::Registration)
        ->and(AuditLog::query()->sole()->action)->toBe('tournament.reset');
});

it('covers every table with tournament data', function () {
    $framework = ['migrations', 'cache', 'cache_locks'];
    $tables = collect(DB::select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%'"))
        ->pluck('name')
        ->diff($framework)
        ->sort()
        ->values()
        ->all();

    expect($tables)->toBe(collect(TournamentReset::TABLES)->sort()->values()->all());
});

it('logs out players from before the reset', function () {
    $old = Player::factory()->create();

    asAdmin()->post('/admin/reset');

    // A new player must not inherit the id still in the old session cookie.
    $new = Player::factory()->create();
    expect($new->id)->toBeGreaterThan($old->id);

    $this->withSession([Auth::guard('web')->getName() => $old->id])
        ->get('/')
        ->assertRedirect('/register');
});

it('lets only the admin reset the tournament', function () {
    $player = Player::factory()->create();

    $this->post('/admin/reset')->assertRedirect('/admin/login');
    $this->actingAs($player)->post('/admin/reset')->assertRedirect('/admin/login');

    expect(Player::query()->count())->toBe(1);
});

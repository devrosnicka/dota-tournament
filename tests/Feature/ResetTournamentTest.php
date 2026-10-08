<?php

use App\Console\Commands\ResetTournament;
use App\Enums\Phase;
use App\Models\AuditLog;
use App\Models\Player;
use App\Tournament\TournamentSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

it('deletes the whole tournament and reopens registration', function () {
    startFinal();
    completeDraft();

    $this->artisan('tournament:reset')
        ->expectsConfirmation('Opravdu smazat celý turnaj?', 'yes')
        ->assertSuccessful();

    foreach (ResetTournament::TABLES as $table) {
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

    expect($tables)->toBe(collect(ResetTournament::TABLES)->sort()->values()->all());
});

it('logs out players from before the reset', function () {
    $old = Player::factory()->create();

    $this->artisan('tournament:reset', ['--force' => true])->assertSuccessful();

    // A new player must not inherit the id still in the old session cookie.
    $new = Player::factory()->create();
    expect($new->id)->toBeGreaterThan($old->id);

    $this->withSession([Auth::guard('web')->getName() => $old->id])
        ->get('/')
        ->assertRedirect('/register');
});

it('keeps everything when not confirmed', function () {
    Player::factory()->count(3)->create();
    setPhase(Phase::GroupStage);

    $this->artisan('tournament:reset')
        ->expectsConfirmation('Opravdu smazat celý turnaj?', 'no')
        ->assertFailed();

    expect(Player::query()->count())->toBe(3)
        ->and(app(TournamentSettings::class)->phase())->toBe(Phase::GroupStage);
});

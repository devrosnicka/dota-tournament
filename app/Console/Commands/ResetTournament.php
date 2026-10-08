<?php

namespace App\Console\Commands;

use App\Tournament\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Deletes all tournament data and starts again from registration, e.g. after
 * a trial run.
 *
 * Rows are deleted, not truncated: SQLite then keeps counting player ids, so
 * a player id left in an old session cookie never matches a new player and
 * the old session just becomes a guest.
 */
class ResetTournament extends Command
{
    protected $signature = 'tournament:reset {--force : Skip the confirmation}';

    protected $description = 'Delete all players, rankings, schedule, results and the final, and reopen registration';

    /**
     * Tournament tables, children before the tables they reference.
     */
    public const TABLES = [
        'final_roles',
        'final_picks',
        'final_setup',
        'tiebreak_orders',
        'match_players',
        'matches',
        'round_sits',
        'rounds',
        'rankings',
        'audit_log',
        'players',
        'settings',
    ];

    public function handle(AuditLogger $audit): int
    {
        $players = DB::table('players')->count();
        $rounds = DB::table('rounds')->count();

        $this->warn("Smaže se {$players} hráčů, {$rounds} kol a všechna hodnocení, výsledky, finále i audit log.");

        if (! $this->option('force') && ! $this->confirm('Opravdu smazat celý turnaj?')) {
            $this->info('Nic se nesmazalo.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($audit, $players, $rounds): void {
            foreach (self::TABLES as $table) {
                DB::table($table)->delete();
            }

            $audit->system('tournament.reset', ['players' => $players, 'rounds' => $rounds]);
        });

        $this->info('Turnaj je smazaný a znovu ve fázi Registrace.');

        return self::SUCCESS;
    }
}

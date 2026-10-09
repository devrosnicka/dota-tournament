<?php

namespace App\Tournament;

use App\Models\Player;
use Illuminate\Support\Facades\DB;

/**
 * Deletes all tournament data so it starts again from registration, e.g.
 * after a trial run.
 *
 * Rows are deleted, not truncated: SQLite then keeps counting player ids, so
 * a player id left in an old session cookie never matches a new player and
 * the old session just becomes a guest.
 */
final class TournamentReset
{
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

    public function __construct(private readonly AuditLogger $audit) {}

    public function run(): void
    {
        DB::transaction(function (): void {
            $players = Player::query()->count();

            foreach (self::TABLES as $table) {
                DB::table($table)->delete();
            }

            $this->audit->admin('tournament.reset', ['players' => $players]);
        });
    }
}

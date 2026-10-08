<?php

namespace App\Tournament;

use App\Domain\Seeding\SeedEntry;
use App\Domain\Seeding\SeedingService;
use App\Enums\Phase;
use App\Models\Player;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Players' rankings of each other and the seeding computed from them
 * (SPEC §1.2). Raw rankings never leave this class except as the rater's own
 * order.
 */
final class Rankings
{
    public function __construct(
        private readonly TournamentSettings $settings,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * The players the rater has to order: everyone active except themselves.
     *
     * @return Collection<int, Player>
     */
    public function ratees(Player $rater): Collection
    {
        return Player::query()->active()->whereKeyNot($rater->id)->get()->keyBy('id');
    }

    /**
     * The rater's saved order, or null if they have not submitted.
     *
     * @return list<int>|null
     */
    public function savedOrder(Player $rater): ?array
    {
        $order = DB::table('rankings')
            ->where('rater_id', $rater->id)
            ->orderBy('position')
            ->pluck('ratee_id')
            ->map(fn (mixed $id) => (int) $id)
            ->all();

        return $order === [] ? null : array_values($order);
    }

    /**
     * @param  list<int>  $order  ratee ids from best to worst
     */
    public function save(Player $rater, array $order): void
    {
        if ($this->settings->phase() !== Phase::Ranking) {
            throw ValidationException::withMessages(['order' => 'Hodnocení je uzavřené.']);
        }

        $expected = $this->ratees($rater)->keys()->sort()->values()->all();
        $given = $order;
        sort($given);

        if ($given !== $expected) {
            throw ValidationException::withMessages(['order' => 'Seznam hráčů se mezitím změnil. Obnov stránku.']);
        }

        DB::transaction(function () use ($rater, $order): void {
            DB::table('rankings')->where('rater_id', $rater->id)->delete();
            DB::table('rankings')->insert(array_map(fn (int $rateeId, int $index) => [
                'rater_id' => $rater->id,
                'ratee_id' => $rateeId,
                'position' => $index + 1,
            ], $order, array_keys($order)));
        });

        // Deliberately without the order itself.
        $this->audit->player($rater, 'ranking.saved');
    }

    /**
     * @return list<int>
     */
    public function submittedPlayerIds(): array
    {
        $ids = DB::table('rankings')
            ->join('players', 'players.id', '=', 'rankings.rater_id')
            ->where('players.status', 'active')
            ->distinct()
            ->pluck('rater_id')
            ->map(fn (mixed $id) => (int) $id)
            ->values()
            ->all();

        return array_values($ids);
    }

    /**
     * Seeding of the active players computed from the current rankings.
     *
     * @return list<SeedEntry>
     */
    public function seeding(): array
    {
        /** @var list<int> $playerIds */
        $playerIds = Player::query()->active()->orderBy('id')->pluck('id')->all();
        $rankings = [];

        foreach (DB::table('rankings')->orderBy('rater_id')->orderBy('position')->get() as $row) {
            $rankings[(int) $row->rater_id][] = (int) $row->ratee_id;
        }

        return (new SeedingService)->seed($playerIds, $rankings, $this->settings->lottery());
    }

    /**
     * Freeze the seeding when the ranking phase closes; the schedule is
     * generated from this snapshot and it never changes afterwards.
     */
    public function snapshot(): void
    {
        foreach ($this->seeding() as $entry) {
            Player::query()->whereKey($entry->playerId)->update([
                'seed_rank' => $entry->rank,
                'seed_score' => $entry->score,
            ]);
        }
    }

    public function clearSnapshot(): void
    {
        Player::query()->update(['seed_rank' => null, 'seed_score' => null]);
    }
}

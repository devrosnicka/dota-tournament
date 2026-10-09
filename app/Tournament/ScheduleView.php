<?php

namespace App\Tournament;

use App\Domain\Schedule\FormatResolver;
use App\Domain\Schedule\ScheduleAnalysis;
use App\Domain\Schedule\ScheduleAnalyzer;
use App\Enums\Side;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Round;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Turns the stored schedule into page props. Seeds are included only for
 * the admin.
 */
final class ScheduleView
{
    public function __construct(private readonly Schedule $schedule) {}

    /**
     * Format of a round for the given number of active players, null when
     * the tournament cannot be played with that many.
     *
     * @return array{name: string, matches: int, sitting: int}|null
     */
    public function format(int $players): ?array
    {
        try {
            $format = (new FormatResolver)->resolve($players);
        } catch (InvalidArgumentException) {
            return null;
        }

        return ['name' => $format->name(), 'matches' => $format->matches, 'sitting' => $format->sitting()];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rounds(bool $withSeeds): array
    {
        $positions = $withSeeds ? $this->schedule->seedPositions() : [];
        $player = fn (Player $p) => $withSeeds
            ? ['id' => $p->id, 'nick' => $p->nick, 'seed' => $positions[$p->id] ?? null]
            : ['id' => $p->id, 'nick' => $p->nick];

        return array_values($this->schedule->rounds()->map(fn (Round $round) => [
            'id' => $round->id,
            'number' => $round->number,
            'format' => $round->format,
            'status' => $round->status->value,
            'sitters' => $round->sitters->sortBy('nick')->map($player)->values()->all(),
            'matches' => $round->matches->map(fn (GameMatch $match) => [
                'id' => $match->id,
                'lobby' => $match->lobby,
                'teamA' => $this->team($match, Side::A)->map($player)->values()->all(),
                'teamB' => $this->team($match, Side::B)->map($player)->values()->all(),
                'winner' => $match->winner?->value,
                'killsA' => $match->kills_a,
                'killsB' => $match->kills_b,
            ])->values()->all(),
        ])->all());
    }

    /**
     * Admin statistics and warnings about broken sit rules.
     *
     * @return array{maxTeammates: int, sits: list<array{nick: string, count: int}>, seedDiffs: list<list<float>>, warnings: list<string>}
     */
    public function analysis(): array
    {
        $rounds = $this->schedule->rounds();
        $analysis = (new ScheduleAnalyzer)->analyze($this->schedule->plans($rounds), $this->schedule->seedPositions());
        $nicks = Player::query()->pluck('nick', 'id');

        return [
            'maxTeammates' => $analysis->maxTeammates,
            'sits' => array_values(collect($analysis->sits)
                ->map(fn (int $count, int $id) => ['nick' => (string) $nicks[$id], 'count' => $count])
                ->sortBy([['count', 'desc'], ['nick', 'asc']])
                ->values()
                ->all()),
            'seedDiffs' => $analysis->seedDiffs,
            'warnings' => $this->warnings($analysis, $nicks->all()),
        ];
    }

    /**
     * @return Collection<int, Player>
     */
    private function team(GameMatch $match, Side $side): Collection
    {
        return $match->players
            ->filter(fn (Player $p) => $p->getRelationValue('pivot')?->getAttribute('side') === $side->value)
            ->sortBy('nick')
            ->values()
            ->toBase();
    }

    /**
     * @param  array<int, string>  $nicks
     * @return list<string>
     */
    private function warnings(ScheduleAnalysis $analysis, array $nicks): array
    {
        $warnings = [];

        foreach ($analysis->unevenAfterRounds as $round) {
            $warnings[] = "Po {$round}. kole se počty sezení liší o víc než 1.";
        }

        foreach ($analysis->consecutiveSits as ['round' => $round, 'player' => $player]) {
            $previous = $round - 1;
            $warnings[] = "{$nicks[$player]} sedí v {$previous}. i {$round}. kole.";
        }

        return $warnings;
    }
}

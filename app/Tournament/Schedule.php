<?php

namespace App\Tournament;

use App\Domain\Schedule\FormatResolver;
use App\Domain\Schedule\GeneratorConfig;
use App\Domain\Schedule\History;
use App\Domain\Schedule\MatchPlan;
use App\Domain\Schedule\RoundPlan;
use App\Domain\Schedule\ScheduleGenerator;
use App\Enums\MatchStage;
use App\Enums\Phase;
use App\Enums\RoundStatus;
use App\Enums\Side;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Round;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The group stage schedule stored in rounds, round_sits, matches and
 * match_players (SPEC §1.5-1.7).
 */
final class Schedule
{
    public const MAX_ROUNDS = 10;

    public function __construct(
        private readonly TournamentSettings $settings,
        private readonly AuditLogger $audit,
    ) {}

    public function exists(): bool
    {
        return Round::query()->exists();
    }

    public function roundsSetting(): int
    {
        $rounds = $this->settings->get('rounds');

        return is_int($rounds) ? $rounds : (int) config('tournament.default_rounds');
    }

    /**
     * @return Collection<int, Round>
     */
    public function rounds(): Collection
    {
        return Round::query()->with(['matches.players', 'sitters'])->orderBy('number')->get();
    }

    /**
     * Active players from the strongest seed.
     *
     * @return list<int>
     */
    public function seededPlayers(): array
    {
        return array_values(Player::query()->active()->orderBy('seed_rank')->orderBy('id')->pluck('id')->all());
    }

    /**
     * Position of every seeded player, withdrawn ones included.
     *
     * @return array<int, int>
     */
    public function seedPositions(): array
    {
        $ids = Player::query()->orderBy('seed_rank')->orderBy('id')->pluck('id')->all();

        return array_combine($ids, range(1, max(1, count($ids)))) ?: [];
    }

    /**
     * @param  Collection<int, Round>  $rounds
     * @return list<RoundPlan>
     */
    public function plans(Collection $rounds): array
    {
        $formats = new FormatResolver;

        return array_values($rounds->map(function (Round $round) use ($formats) {
            $matches = array_values($round->matches->map(fn (GameMatch $match) => new MatchPlan($match->team(Side::A), $match->team(Side::B)))->all());
            $sitters = array_values($round->sitters->pluck('id')->all());
            $players = count($sitters) + array_sum(array_map(fn (MatchPlan $m) => count($m->teamA) + count($m->teamB), $matches));

            return new RoundPlan($formats->resolve($players), $sitters, $matches);
        })->all());
    }

    public function generate(int $rounds): void
    {
        if ($this->settings->phase() !== Phase::ScheduleReview) {
            throw ValidationException::withMessages(['rounds' => 'Rozpis lze generovat jen při přípravě rozpisu.']);
        }

        if ($rounds < 1 || $rounds > self::MAX_ROUNDS) {
            throw ValidationException::withMessages(['rounds' => 'Počet kol musí být 1 až '.self::MAX_ROUNDS.'.']);
        }

        $seed = random_int(1, PHP_INT_MAX);
        $generated = $this->generator()->generate($this->seededPlayers(), $rounds, new History, $seed);

        DB::transaction(function () use ($generated, $rounds, $seed): void {
            Round::query()->delete();
            $this->persist($generated->rounds, 1);

            $this->settings->set('rounds', $rounds);
            $this->settings->set('schedule_seed', $seed);
        });

        $this->audit->admin('schedule.generated', ['rounds' => $rounds, 'seed' => $seed, 'cost' => $generated->cost]);
    }

    public function delete(): void
    {
        Round::query()->delete();
    }

    /**
     * Swap two players within a round: between teams or matches, or a
     * playing player with a sitting one.
     */
    public function swap(Round $round, int $first, int $second): void
    {
        if ($this->settings->phase() !== Phase::ScheduleReview || $round->status !== RoundStatus::Planned) {
            throw ValidationException::withMessages(['players' => 'Kolo už nejde upravit.']);
        }

        $slots = $this->slots($round);

        if (! isset($slots[$first], $slots[$second]) || $first === $second) {
            throw ValidationException::withMessages(['players' => 'Vyber dva různé hráče z tohoto kola.']);
        }

        if ($slots[$first] === $slots[$second]) {
            throw ValidationException::withMessages(['players' => 'Oba hráči už jsou na stejné straně.']);
        }

        DB::transaction(function () use ($round, $slots, $first, $second): void {
            foreach ([[$first, $slots[$second]], [$second, $slots[$first]]] as [$player, $slot]) {
                $this->leave($round, $player);
                $this->take($round, $player, $slot);
            }
        });

        $this->audit->admin('schedule.swapped', ['round' => $round->number, 'players' => [$first, $second]]);
    }

    /**
     * @param  list<RoundPlan>  $plans
     */
    public function persist(array $plans, int $firstNumber): void
    {
        foreach ($plans as $index => $plan) {
            $round = Round::query()->create([
                'number' => $firstNumber + $index,
                'format' => $plan->format->name(),
                'status' => RoundStatus::Planned,
            ]);

            $round->sitters()->attach($plan->sitters);

            foreach ($plan->matches as $lobby => $match) {
                $game = $round->matches()->create(['stage' => MatchStage::Group, 'lobby' => $lobby + 1]);
                $game->players()->attach(
                    array_fill_keys($match->teamA, ['side' => Side::A->value])
                    + array_fill_keys($match->teamB, ['side' => Side::B->value]),
                );
            }
        }
    }

    public function generator(): ScheduleGenerator
    {
        /** @var array{weights?: array<string, int|float>, seed_tolerance?: int|float, samples_per_round?: int, restarts?: int} $config */
        $config = config('tournament.generator');

        return new ScheduleGenerator(GeneratorConfig::fromArray($config));
    }

    /**
     * Where each player of the round is: "sit" or "<match id>:<side>".
     *
     * @return array<int, string>
     */
    private function slots(Round $round): array
    {
        $slots = [];

        foreach ($round->sitters()->pluck('players.id') as $player) {
            $slots[(int) $player] = 'sit';
        }

        $rows = DB::table('match_players')
            ->join('matches', 'matches.id', '=', 'match_players.match_id')
            ->where('matches.round_id', $round->id)
            ->get(['match_players.player_id', 'match_players.match_id', 'match_players.side']);

        foreach ($rows as $row) {
            $slots[(int) $row->player_id] = $row->match_id.':'.$row->side;
        }

        return $slots;
    }

    private function leave(Round $round, int $player): void
    {
        $round->sitters()->detach($player);
        DB::table('match_players')
            ->where('player_id', $player)
            ->whereIn('match_id', $round->matches()->pluck('id'))
            ->delete();
    }

    private function take(Round $round, int $player, string $slot): void
    {
        if ($slot === 'sit') {
            $round->sitters()->attach($player);

            return;
        }

        [$matchId, $side] = explode(':', $slot);
        DB::table('match_players')->insert(['match_id' => (int) $matchId, 'player_id' => $player, 'side' => $side]);
    }
}

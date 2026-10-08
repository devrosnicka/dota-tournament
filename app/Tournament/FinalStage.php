<?php

namespace App\Tournament;

use App\Domain\Final\FinalDraft;
use App\Domain\Final\FinalSeries;
use App\Domain\Final\MapResult;
use App\Enums\Advantage;
use App\Enums\DraftStage;
use App\Enums\MatchStage;
use App\Enums\Phase;
use App\Enums\Side;
use App\Models\FinalPick;
use App\Models\FinalRole;
use App\Models\FinalSetup;
use App\Models\GameMatch;
use App\Models\Player;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * The final: captains, advantage, live draft, roles and the Bo3 series
 * (SPEC §1.10, §2.6). Every move is checked in a transaction against the
 * current state, so two phones tapping at once cannot both pick.
 *
 * Side A is always the team of captain 1 (1st place), side B of captain 2.
 */
final class FinalStage
{
    public function __construct(
        private readonly TournamentSettings $settings,
        private readonly Standings $standings,
        private readonly AuditLogger $audit,
    ) {}

    public function setup(): ?FinalSetup
    {
        return FinalSetup::query()->first();
    }

    /**
     * Freeze the finalists when the group stage ends.
     */
    public function start(): void
    {
        $this->reset();

        $table = $this->standings->group();
        $finalists = [];

        foreach ($table->qualified() as $player) {
            $finalists[$player] = $table->row($player)->position ?? 0;
        }

        asort($finalists);
        [$captain1, $captain2] = array_keys($finalists);

        FinalSetup::query()->create([
            'captain1_id' => $captain1,
            'captain2_id' => $captain2,
            'finalists' => $finalists,
        ]);
    }

    public function reset(): void
    {
        GameMatch::query()->where('stage', MatchStage::Final)->delete();
        FinalRole::query()->delete();
        FinalPick::query()->delete();
        FinalSetup::query()->delete();
    }

    public function draft(): ?FinalDraft
    {
        $setup = $this->setup();

        if ($setup === null) {
            return null;
        }

        $placement = [];

        foreach ($setup->finalists as $player => $position) {
            $placement[(int) $player] = (int) $position;
        }

        $captains = [$setup->captain1_id, $setup->captain2_id];

        return new FinalDraft(
            captainA: $setup->captain1_id,
            captainB: $setup->captain2_id,
            pool: array_values(array_diff(array_keys($placement), $captains)),
            placement: $placement,
            advantage: $setup->advantage_choice,
            picks: array_values(FinalPick::query()->orderBy('pick_number')->pluck('player_id')->map(fn (mixed $id) => (int) $id)->all()),
            roles: FinalRole::query()->pluck('position', 'player_id')->map(fn (mixed $role) => (int) $role)->all(),
        );
    }

    /**
     * @param  Player|null  $by  null when the admin acts
     */
    public function chooseAdvantage(Advantage $advantage, ?Player $by): void
    {
        $this->move(function (FinalDraft $draft, FinalSetup $setup) use ($advantage, $by): void {
            if ($draft->stage() !== DraftStage::Advantage) {
                throw new InvalidArgumentException('Výhoda už je zvolená.');
            }

            if ($by !== null && $by->id !== $setup->captain1_id) {
                throw new InvalidArgumentException('Výhodu volí kapitán z 1. místa.');
            }

            $setup->update(['advantage_choice' => $advantage]);
            $this->log($by, 'final.advantage', ['choice' => $advantage->value]);
        });
    }

    public function pick(int $player, ?Player $by): void
    {
        $this->move(function (FinalDraft $draft) use ($player, $by): void {
            $side = $draft->pickingSide() ?? throw new InvalidArgumentException('Teď se hráči nevybírají.');
            $captain = $draft->captain($side);

            if ($by !== null && $by->id !== $captain) {
                throw new InvalidArgumentException('Teď vybírá druhý kapitán.');
            }

            $draft->withPick($player);

            FinalPick::query()->create([
                'pick_number' => count($draft->picks) + 1,
                'captain_id' => $captain,
                'player_id' => $player,
            ]);
            $this->log($by, 'final.pick', ['pick' => count($draft->picks) + 1, 'player_id' => $player]);
        });
    }

    public function chooseRole(int $player, int $role, ?Player $by): void
    {
        $this->move(function (FinalDraft $draft) use ($player, $role, $by): void {
            if ($by !== null && $by->id !== $player) {
                throw new InvalidArgumentException('Roli si volí každý sám.');
            }

            $draft->withRole($player, $role);
            $side = $draft->sideOf($player) ?? Side::A;

            FinalRole::query()->create([
                'player_id' => $player,
                'captain_id' => $draft->captain($side),
                'position' => $role,
            ]);
            $this->log($by, 'final.role', ['player_id' => $player, 'role' => $role]);
        });
    }

    /**
     * Admin: take back the last move (role, then pick, then advantage).
     */
    public function undo(): void
    {
        $this->move(function (FinalDraft $draft, FinalSetup $setup): void {
            $role = FinalRole::query()->latest('id')->first();
            $pick = FinalPick::query()->orderByDesc('pick_number')->first();

            match (true) {
                $role !== null => $role->delete(),
                $pick !== null => $pick->delete(),
                $setup->advantage_choice !== null => $setup->update(['advantage_choice' => null]),
                default => throw new InvalidArgumentException('Není co vracet.'),
            };

            $this->audit->admin('final.undo');
        });
    }

    public function series(): FinalSeries
    {
        return new FinalSeries(array_values($this->maps()->map(fn (GameMatch $map) => new MapResult(
            $map->winner ?? Side::A,
            $map->kills_a,
            $map->kills_b,
            $map->radiant_side,
            $map->first_pick_side,
        ))->all()));
    }

    /**
     * @return Collection<int, GameMatch>
     */
    public function maps(): Collection
    {
        return GameMatch::query()->where('stage', MatchStage::Final)->orderBy('map_number')->get();
    }

    public function recordMap(Side $winner, ?int $killsA, ?int $killsB, ?Side $radiant, ?Side $firstPick): void
    {
        $draft = $this->draft();
        $map = $this->series()->nextMap();

        if ($this->settings->phase() !== Phase::Final || $draft === null || $map === null) {
            throw ValidationException::withMessages(['winner' => 'Další mapa se teď zadat nedá.']);
        }

        DB::transaction(function () use ($draft, $map, $winner, $killsA, $killsB, $radiant, $firstPick): void {
            $game = GameMatch::query()->create([
                'stage' => MatchStage::Final,
                'map_number' => $map,
                'winner' => $winner,
                'kills_a' => $killsA,
                'kills_b' => $killsB,
                'radiant_side' => $radiant,
                'first_pick_side' => $firstPick,
                'reported_at' => now(),
            ]);

            $game->players()->attach(
                array_fill_keys($draft->team(Side::A), ['side' => Side::A->value])
                + array_fill_keys($draft->team(Side::B), ['side' => Side::B->value]),
            );
        });

        $this->audit->admin('final.map', ['map' => $map, 'winner' => $winner->value]);
    }

    public function deleteLastMap(): void
    {
        $last = GameMatch::query()->where('stage', MatchStage::Final)->orderByDesc('map_number')->first();

        if ($this->settings->phase() !== Phase::Final || $last === null) {
            throw ValidationException::withMessages(['winner' => 'Není co smazat.']);
        }

        $last->delete();
        $this->audit->admin('final.map_deleted', ['map' => $last->map_number]);
    }

    /**
     * Final points of every finalist (SPEC §1.10).
     *
     * @return array<int, int>
     */
    public function points(): array
    {
        $draft = $this->draft();

        if ($draft === null || $draft->stage() !== DraftStage::Done) {
            return [];
        }

        $series = $this->series();
        $points = [];

        foreach ([Side::A, Side::B] as $side) {
            foreach ($draft->team($side) as $player) {
                $points[$player] = $series->points($side);
            }
        }

        return $points;
    }

    /**
     * Run a draft move in a transaction against fresh state; domain errors
     * become validation errors for the page.
     *
     * @param  callable(FinalDraft, FinalSetup): void  $move
     */
    private function move(callable $move): void
    {
        if ($this->settings->phase() !== Phase::FinalDraft) {
            throw ValidationException::withMessages(['draft' => 'Draft teď neprobíhá.']);
        }

        try {
            DB::transaction(function () use ($move): void {
                $setup = $this->setup() ?? throw new InvalidArgumentException('Finále ještě nezačalo.');
                $move($this->draft() ?? throw new InvalidArgumentException('Finále ještě nezačalo.'), $setup);
            });
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['draft' => $e->getMessage()]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['draft' => 'Někdo byl rychlejší, stránka se obnovila.']);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function log(?Player $by, string $action, array $payload): void
    {
        $by === null ? $this->audit->admin($action, $payload) : $this->audit->player($by, $action, $payload);
    }
}

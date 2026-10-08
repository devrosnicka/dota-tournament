<?php

namespace App\Domain\Final;

use App\Enums\Advantage;
use App\Enums\DraftStage;
use App\Enums\Side;
use InvalidArgumentException;

/**
 * State of the final draft (SPEC §1.10): the advantage choice, the snake
 * pick of players and the choice of roles.
 *
 * Side A is the team of captain 1 (1st place), side B of captain 2. The
 * snake order A, B, B, A, A, B, B, A starts with whichever captain holds the
 * player pick advantage. Within a team, players choose a role 1-5 in order
 * of their group stage placement, captains included; both teams choose at
 * the same time.
 */
final readonly class FinalDraft
{
    /** Snake order; "first" is the captain with the first player pick. */
    public const SNAKE = ['first', 'second', 'second', 'first', 'first', 'second', 'second', 'first'];

    public const ROLES = [1, 2, 3, 4, 5];

    /**
     * @param  list<int>  $pool  the eight finalists to pick from
     * @param  array<int, int>  $placement  finalist id => group stage position
     * @param  list<int>  $picks  picked player ids in pick order
     * @param  array<int, int>  $roles  player id => role
     */
    public function __construct(
        public int $captainA,
        public int $captainB,
        public array $pool,
        public array $placement,
        public ?Advantage $advantage = null,
        public array $picks = [],
        public array $roles = [],
    ) {}

    public function stage(): DraftStage
    {
        return match (true) {
            $this->advantage === null => DraftStage::Advantage,
            count($this->picks) < count(self::SNAKE) => DraftStage::Picks,
            count($this->roles) < 10 => DraftStage::Roles,
            default => DraftStage::Done,
        };
    }

    /**
     * Side with the first player pick; the other side picks side and first
     * pick on map 1.
     */
    public function firstPickSide(): ?Side
    {
        return match ($this->advantage) {
            null => null,
            Advantage::PlayerPick => Side::A,
            Advantage::SidePick => Side::B,
        };
    }

    /**
     * @return list<Side> side picking at each pick number
     */
    public function pickOrder(): array
    {
        $first = $this->firstPickSide() ?? Side::A;

        return array_map(fn (string $slot) => $slot === 'first' ? $first : $first->other(), self::SNAKE);
    }

    public function captain(Side $side): int
    {
        return $side === Side::A ? $this->captainA : $this->captainB;
    }

    public function sideOf(int $player): ?Side
    {
        foreach ([Side::A, Side::B] as $side) {
            if (in_array($player, $this->team($side), true)) {
                return $side;
            }
        }

        return null;
    }

    /**
     * Side on turn to pick a player, or null outside the pick stage.
     */
    public function pickingSide(): ?Side
    {
        return $this->stage() === DraftStage::Picks ? $this->pickOrder()[count($this->picks)] : null;
    }

    /**
     * @return list<int>
     */
    public function available(): array
    {
        return array_values(array_diff($this->pool, $this->picks));
    }

    /**
     * Captain first, then their picks.
     *
     * @return list<int>
     */
    public function team(Side $side): array
    {
        $team = [$this->captain($side)];

        foreach ($this->picks as $index => $player) {
            if ($this->pickOrder()[$index] === $side) {
                $team[] = $player;
            }
        }

        return $team;
    }

    public function withPick(int $player): self
    {
        if ($this->stage() !== DraftStage::Picks || ! in_array($player, $this->available(), true)) {
            throw new InvalidArgumentException('Tohoto hráče teď nelze vybrat.');
        }

        return new self($this->captainA, $this->captainB, $this->pool, $this->placement, $this->advantage, [...$this->picks, $player], $this->roles);
    }

    /**
     * Team ordered by group stage placement: the order of choosing roles.
     *
     * @return list<int>
     */
    public function roleOrder(Side $side): array
    {
        $team = $this->team($side);
        usort($team, fn (int $a, int $b) => $this->placement[$a] <=> $this->placement[$b]);

        return $team;
    }

    /**
     * Player of the team who chooses a role now.
     */
    public function roleTurn(Side $side): ?int
    {
        if ($this->stage() !== DraftStage::Roles) {
            return null;
        }

        foreach ($this->roleOrder($side) as $player) {
            if (! isset($this->roles[$player])) {
                return $player;
            }
        }

        return null;
    }

    /**
     * @return list<int>
     */
    public function freeRoles(Side $side): array
    {
        $taken = array_map(fn (int $player) => $this->roles[$player] ?? null, $this->team($side));

        return array_values(array_diff(self::ROLES, $taken));
    }

    public function withRole(int $player, int $role): self
    {
        $side = $this->sideOf($player);

        if ($side === null || $this->roleTurn($side) !== $player || ! in_array($role, $this->freeRoles($side), true)) {
            throw new InvalidArgumentException('Tuto roli teď nelze zvolit.');
        }

        return new self($this->captainA, $this->captainB, $this->pool, $this->placement, $this->advantage, $this->picks, $this->roles + [$player => $role]);
    }
}

<?php

namespace App\Domain\Final;

use App\Enums\Side;

/**
 * Best of three (SPEC §1.10). Map 3 only at 1:1. Before map 1 the team with
 * the side pick advantage chooses both side and first pick; before maps 2
 * and 3 the loser of the previous map chooses side or first pick and the
 * other team gets the other.
 *
 * Final points per player: +1 for every map their team won, +1 for winning
 * the series. 2:0 gives 3 and 0, 2:1 gives 3 and 1.
 */
final readonly class FinalSeries
{
    public const WINS_NEEDED = 2;

    /**
     * @param  list<MapResult>  $maps  played maps in order
     */
    public function __construct(public array $maps = []) {}

    public function wins(Side $side): int
    {
        return count(array_filter($this->maps, fn (MapResult $map) => $map->winner === $side));
    }

    public function winner(): ?Side
    {
        foreach ([Side::A, Side::B] as $side) {
            if ($this->wins($side) >= self::WINS_NEEDED) {
                return $side;
            }
        }

        return null;
    }

    public function nextMap(): ?int
    {
        return $this->winner() === null ? count($this->maps) + 1 : null;
    }

    /**
     * Who decides before the next map, given the side holding the side pick
     * advantage. On map 1 they choose both side and first pick.
     */
    public function chooser(Side $sidePickHolder): ?Side
    {
        if ($this->nextMap() === null) {
            return null;
        }

        $last = $this->maps === [] ? null : $this->maps[array_key_last($this->maps)];

        return $last === null ? $sidePickHolder : $last->winner->other();
    }

    public function points(Side $side): int
    {
        return $this->wins($side) + ($this->winner() === $side ? 1 : 0);
    }
}

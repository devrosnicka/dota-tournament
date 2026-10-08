<?php

namespace App\Models;

use App\Enums\TiebreakContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Result of a 1v1 shootout entered by the admin, winners first.
 *
 * @property int $id
 * @property TiebreakContext $context
 * @property list<int> $ordered_player_ids
 * @property string|null $note
 */
class TiebreakOrder extends Model
{
    protected $fillable = [
        'context',
        'ordered_player_ids',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'context' => TiebreakContext::class,
            'ordered_player_ids' => 'array',
        ];
    }

    /**
     * @return list<int>|null
     */
    public static function orderFor(TiebreakContext $context): ?array
    {
        $order = self::query()->where('context', $context)->first()?->ordered_player_ids;

        return $order === null ? null : array_map(intval(...), $order);
    }
}

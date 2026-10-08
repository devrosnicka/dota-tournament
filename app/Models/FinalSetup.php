<?php

namespace App\Models;

use App\Enums\Advantage;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $captain1_id
 * @property int $captain2_id
 * @property Advantage|null $advantage_choice
 * @property array<int|string, int> $finalists
 */
class FinalSetup extends Model
{
    protected $table = 'final_setup';

    protected $fillable = [
        'captain1_id',
        'captain2_id',
        'advantage_choice',
        'finalists',
    ];

    protected function casts(): array
    {
        return [
            'advantage_choice' => Advantage::class,
            'finalists' => 'array',
        ];
    }
}

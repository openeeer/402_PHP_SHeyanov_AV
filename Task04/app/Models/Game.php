<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'player_name',
        'expression',
        'correct_answer',
        'started_at',
    ];

    protected function casts(): array
    {
        return [
            'correct_answer' => 'integer',
            'started_at' => 'datetime',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(Step::class)->orderBy('id');
    }
}

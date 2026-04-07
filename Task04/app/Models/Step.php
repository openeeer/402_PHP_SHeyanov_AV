<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Step extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'game_id',
        'user_answer',
        'is_correct',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'user_answer' => 'integer',
            'is_correct' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoodGenre extends Model
{
    protected $fillable = ['mood_id', 'genre_name'];

    public function mood(): BelongsTo
    {
        return $this->belongsTo(Mood::class);
    }
}

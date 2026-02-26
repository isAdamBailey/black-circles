<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoodExcludeStyle extends Model
{
    protected $fillable = ['mood_id', 'style_name'];

    public function mood(): BelongsTo
    {
        return $this->belongsTo(Mood::class);
    }
}

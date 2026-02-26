<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mood extends Model
{
    protected $fillable = ['slug', 'label', 'emoji', 'sort_order'];

    public function genres(): HasMany
    {
        return $this->hasMany(MoodGenre::class);
    }

    public function styles(): HasMany
    {
        return $this->hasMany(MoodStyle::class);
    }

    public function excludeStyles(): HasMany
    {
        return $this->hasMany(MoodExcludeStyle::class, 'mood_id');
    }

    public function getGenreNames(): array
    {
        return $this->genres->pluck('genre_name')->all();
    }

    public function getStyleNames(): array
    {
        return $this->styles->pluck('style_name')->all();
    }

    public function getExcludeStyleNames(): array
    {
        return $this->excludeStyles->pluck('style_name')->all();
    }
}

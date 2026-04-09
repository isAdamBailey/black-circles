<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    protected $fillable = ['name'];

    public function releases(): BelongsToMany
    {
        return $this->belongsToMany(DiscogsRelease::class, 'discogs_release_genre');
    }

    public static function orderedNames(): array
    {
        return static::orderBy('name')->pluck('name')->all();
    }

    public static function orderedNamesInCollection(): array
    {
        return static::whereHas('releases', fn ($q) => $q->whereHas('collectionItem'))
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }
}

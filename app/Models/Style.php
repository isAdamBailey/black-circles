<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Style extends Model
{
    protected $fillable = ['name'];

    public function releases(): BelongsToMany
    {
        return $this->belongsToMany(DiscogsRelease::class, 'discogs_release_style');
    }

    public static function orderedNames(): array
    {
        return static::orderBy('name')->pluck('name')->all();
    }
}

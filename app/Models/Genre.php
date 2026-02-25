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
}

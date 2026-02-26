<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Scout\Searchable;

class DiscogsRelease extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'discogs_id', 'title', 'artist', 'label', 'catalog_number', 'year',
        'cover_image', 'thumb', 'images', 'formats', 'tracklist',
        'videos', 'lowest_price', 'median_price', 'highest_price',
        'discogs_uri', 'notes', 'release_data_cached_at',
    ];

    protected $casts = [
        'images' => 'array',
        'formats' => 'array',
        'tracklist' => 'array',
        'videos' => 'array',
        'release_data_cached_at' => 'datetime',
    ];

    public function collectionItem()
    {
        return $this->hasOne(DiscogsCollectionItem::class, 'discogs_release_id', 'discogs_id');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'discogs_release_genre');
    }

    public function styles(): BelongsToMany
    {
        return $this->belongsToMany(Style::class, 'discogs_release_style');
    }

    public function scopeWhereHasGenres(Builder $query, array $genres): void
    {
        if (empty($genres)) {
            return;
        }
        $query->whereHas('genres', fn ($q) => $q->whereIn('name', $genres));
    }

    public function scopeWhereHasStyles(Builder $query, array $styles): void
    {
        if (empty($styles)) {
            return;
        }
        $query->whereHas('styles', fn ($q) => $q->whereIn('name', $styles));
    }

    public function scopeOrWhereHasStyles(Builder $query, array $styles): void
    {
        if (empty($styles)) {
            return;
        }
        $query->orWhereHas('styles', fn ($q) => $q->whereIn('name', $styles));
    }

    public function scopeWhereDoesntHaveStyles(Builder $query, array $styles): void
    {
        if (empty($styles)) {
            return;
        }
        $query->whereDoesntHave('styles', fn ($q) => $q->whereIn('name', $styles));
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'discogs_id' => $this->discogs_id,
            'title' => $this->title,
            'artist' => $this->artist,
            'label' => $this->label,
            'thumb' => $this->thumb,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->relationLoaded('collectionItem')
            ? $this->collectionItem !== null
            : $this->collectionItem()->exists();
    }

    public function makeAllSearchableUsing($query)
    {
        return $query->whereHas('collectionItem');
    }
}

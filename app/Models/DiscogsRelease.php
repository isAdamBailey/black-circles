<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscogsRelease extends Model
{
    use HasFactory;
    protected $fillable = [
        'discogs_id', 'title', 'artist', 'label', 'catalog_number', 'year',
        'cover_image', 'thumb', 'formats', 'genres', 'styles', 'tracklist',
        'videos', 'lowest_price', 'median_price', 'highest_price',
        'discogs_uri', 'notes', 'release_data_cached_at',
    ];

    protected $casts = [
        'formats' => 'array',
        'genres' => 'array',
        'styles' => 'array',
        'tracklist' => 'array',
        'videos' => 'array',
        'release_data_cached_at' => 'datetime',
    ];

    public function collectionItem()
    {
        return $this->hasOne(DiscogsCollectionItem::class, 'discogs_release_id', 'discogs_id');
    }
}

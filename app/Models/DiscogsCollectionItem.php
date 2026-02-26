<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscogsCollectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'instance_id', 'discogs_release_id', 'folder_id', 'rating', 'notes', 'date_added',
    ];

    protected $casts = [
        'notes' => 'array',
        'date_added' => 'datetime',
    ];

    public function release()
    {
        return $this->belongsTo(DiscogsRelease::class, 'discogs_release_id', 'discogs_id');
    }
}

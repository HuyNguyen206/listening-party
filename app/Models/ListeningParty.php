<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListeningParty extends Model
{
    use HasFactory;

    protected $casts = [
        'is_active' => 'bool',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

//    public function podcast(): HasOneThrough
//    {
//        return $this->hasOneThrough(Podcast::class, Episode::class, 'id', 'id', 'episode_id', 'podcast_id');
//    }
}

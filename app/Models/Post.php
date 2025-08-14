<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

class Post extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'topic_id',
        'user_id',
        'content',
        'media_url',
        'type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }



    public function getRouteKeyName(): string
    {
        return 'id'; // If using UUID as primary key
    }


    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    protected static function booted(): void
    {
        static::created(function (Post $post) {
            $post->user->increment('posts_count');
        });

        static::deleted(function (Post $post) {
            $post->user->decrement('posts_count');
        });
    }

    public function getIsLikedAttribute()
    {
        if (!Auth::check()) {
            return false;
        }

        return $this->likes()->where('user_id', Auth::id())->exists();
    }
}

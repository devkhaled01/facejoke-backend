<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

class Reaction extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'post_id',
        'user_id',
        'type',
        'content',
        'parent_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

//    protected static function booted(): void
//    {
//        static::created(function (Reaction $reaction) {
//            $reaction->user->increment('reactions_count');
//        });
//
//        static::deleted(function (Reaction $reaction) {
//            $reaction->user->decrement('reactions_count');
//            $reaction->post->decrement('reactions_count');
//        });
//    }

    protected static function booted(): void
    {
        static::created(function (Reaction $reaction) {
            $reaction->user?->increment('reactions_count');

            if ($reaction->parent_id) {
                $reaction->parent?->increment('reactions_count');
            }
        });

        static::deleted(function (Reaction $reaction) {
            $reaction->user?->decrement('reactions_count');

            if ($reaction->parent_id) {
                $reaction->parent?->decrement('reactions_count');
            }
        });
    }


    public function getIsLikedAttribute()
    {
        if (!Auth::check()) {
            return false;
        }

        return $this->likes()->where('user_id', Auth::id())->exists();
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Reaction::class, 'parent_id');
    }

}

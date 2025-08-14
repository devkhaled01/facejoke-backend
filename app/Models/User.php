<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    public $incrementing = false;
    protected $keyType = 'string';

    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    protected $fillable = [
        'id',
        'display_name',
        'unique_name',
        'email',
        'password',
        'avatar_url',
        'followers_count',
        'followings_count',
        'posts_count',
        'reactions_count',
        'email_verified_at',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'is_active' => false,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    public function followings()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    // app/Models/User.php

    public function getPaginatedFollowers($perPage = 5)
    {
        return $this->followers()
            ->select('users.id', 'users.display_name', 'users.unique_name')
            ->paginate($perPage);
    }

    public function getPaginatedFollowings($perPage = 5)
    {
        return $this->followings()
            ->select('users.id', 'users.display_name', 'users.unique_name')
            ->paginate($perPage);
    }


    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            // Delete avatar if exists
            if ($user->avatar_url && Storage::disk('public')->exists($user->avatar_url)) {
                Storage::disk('public')->delete($user->avatar_url);
            }

            // Delete post media
            foreach ($user->posts as $post) {
                if ($post->media_url && Storage::disk('public')->exists($post->media_url)) {
                    Storage::disk('public')->delete($post->media_url);
                }
            }

            // Delete reaction media and update post reaction count
            foreach ($user->reactions as $reaction) {
                if (in_array($reaction->type, ['voice', 'video']) && Storage::disk('public')->exists($reaction->content)) {
                    Storage::disk('public')->delete($reaction->content);
                }

                if ($reaction->post_id) {
                    Post::where('id', $reaction->post_id)->decrement('reactions_count');
                }
            }

            // Update like counts on posts
            foreach ($user->likes()->where('likeable_type', Post::class)->get() as $like) {
                Post::where('id', $like->likeable_id)->decrement('likes_count');
            }
        });
    }

    public function getIsFollowedAttribute(): bool
    {
        $currentUser = Auth::user();

        if (!$currentUser || $currentUser->id === $this->id) {
            return false;
        }

        return $this->followers()->where('follower_id', $currentUser->id)->exists();
    }
}

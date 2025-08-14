<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Reaction;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LikeController extends Controller
{
    // Like a post
    public function likePost(Post $post)
    {
        $user = Auth::user();
        if ($post->likes()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Already liked'], 200);
        }
        $like = $post->likes()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
        ]);
        $post->increment('likes_count');
        return response()->json(['message' => 'Liked', 'like' => $like], 200);
    }

    // Unlike a post
    public function unlikePost(Post $post)
    {
        $user = Auth::user();
        if($post->likes()->where('user_id', $user->id)->delete()){
            $post->decrement('likes_count');
        }
        return response()->json([], 204);
    }

    // Like a reaction
    public function likeReaction(Reaction $reaction)
    {
        $user = Auth::user();
        if ($reaction->likes()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Already liked'], 200);
        }
        $like = $reaction->likes()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
        ]);
        $reaction->increment('likes_count');
        return response()->json(['message' => 'Liked', 'like' => $like], 200);
    }

    // Unlike a reaction
    public function unlikeReaction(Reaction $reaction)
    {
        $user = Auth::user();
        if ($reaction->likes()->where('user_id', $user->id)->delete()) {
            $reaction->decrement('likes_count');
        }
        return response()->json([], 204);
    }
}

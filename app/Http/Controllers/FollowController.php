<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FollowController extends Controller
{
    public function follow(User $user)
    {
        $current = Auth::user();

        if ($current->id === $user->id) {
            return response()->json(['message' => 'Cannot follow yourself'], 400);
        }

        if ($current->followings()->where('following_id', $user->id)->exists()) {
            return response()->json(['message' => 'Already following', 'user' => $user->fresh()->setAttribute('is_followed', true)], 200);
        }

        // Attach and increment counts
        $current->followings()->attach($user->id, ['id' => (string) Str::uuid()]);
        $current->increment('followings_count');
        $user->increment('followers_count');

        return response()->json([
            'message' => 'Followed',
            'user' => $user->fresh()->setAttribute('is_followed', true)
        ], 200);
    }

    public function unfollow(User $user)
    {
        $current = Auth::user();

        if ($current->followings()->detach($user->id)) {
            $current->decrement('followings_count');
            $user->decrement('followers_count');
        }

        return response()->json([
            'message' => 'Unfollowed',
            'user' => $user->fresh()->setAttribute('is_followed', false)
        ], 200);
    }

    public function followers(User $user)
    {
        $followers = $user->followers()->get();
        return response()->json($followers);
    }

    public function followings(User $user)
    {
        $followings = $user->followings()->get();
        return response()->json($followings);
    }
}

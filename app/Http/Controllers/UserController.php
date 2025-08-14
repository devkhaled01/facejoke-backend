<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function show(Request $request, User $user)
    {
        // Authenticate via token if not already set
        if (!Auth::check() && $request->bearerToken()) {
            $token = PersonalAccessToken::findToken($request->bearerToken());
            if ($token) {
                Auth::setUser($token->tokenable);
            }
        }

        $currentUser = Auth::user();

        // Check if authenticated user follows the profile user
        $isFollowed = false;
        if ($currentUser && $currentUser->followings()->where('following_id', $user->id)->exists()) {
            $isFollowed = true;
        }

        // Return user data with is_followed field
        return response()->json(
            $user->makeHidden(['email', 'password', 'remember_token'])
                ->setAttribute('is_followed', $isFollowed)
        );
    }

    public function followers(Request $request, User $user)
    {
        $perPage = $request->query('per_page', 5);

        $followers = $user->getPaginatedFollowers($perPage);

        return response()->json($followers);
    }

    public function followings(Request $request, User $user)
    {
        $perPage = $request->query('per_page', 5);

        $followings = $user->getPaginatedFollowings($perPage);

        return response()->json($followings);
    }


}


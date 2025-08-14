<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Reaction;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class ReactionController extends Controller
{
    public function index(Post $post, Request $request)
    {
        // Authenticate if needed
        if (!Auth::check() && $request->bearerToken()) {
            $token = PersonalAccessToken::findToken($request->bearerToken());
            if ($token) {
                Auth::setUser($token->tokenable);
            }
        }

        $userId = Auth::id();

        $likedReactionIds = [];
        if ($userId) {
            $likedReactionIds = Like::where('user_id', $userId)
                ->where('likeable_type', Reaction::class)
                ->pluck('likeable_id')
                ->toArray();
        }

        $reactions = $post->reactions()
            ->whereNull('parent_id') // ✅ only parent reactions
            ->with(['user', 'post.user', 'post.topic'])
            ->latest()
            ->paginate(10);

        return response()->json(
            $reactions->through(function ($reaction) use ($likedReactionIds) {
                return $reaction
                    ->makeHidden('user_id')
                    ->setAttribute('is_liked', in_array($reaction->id, $likedReactionIds));
            })
        );
    }

    public function replies(Reaction $reaction, Request $request)
    {
        // Optional: authenticate user for is_liked if needed
        if (!Auth::check() && $request->bearerToken()) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
            if ($token) {
                Auth::setUser($token->tokenable);
            }
        }

        $userId = Auth::id();
        $likedReactionIds = [];

        if ($userId) {
            $likedReactionIds = Like::where('user_id', $userId)
                ->where('likeable_type', Reaction::class)
                ->pluck('likeable_id')
                ->toArray();
        }

        $replies = $reaction->reactions()
            ->with(['user', 'post.user', 'post.topic']) // preload necessary relationships
            ->latest()
            ->paginate(10);

        return response()->json(
            $replies->through(function ($reply) use ($likedReactionIds) {
                return $reply
                    ->makeHidden('user_id')
                    ->setAttribute('is_liked', in_array($reply->id, $likedReactionIds));
            })
        );
    }



    public function store(Request $request, Post $post)
    {
        $validated = $request->validate([
            'type' => 'required|in:text,video,voice',
            'content' => 'required|string',
        ]);
        $topic = $post->topic;
        if (
            (($validated['type'] === 'text' && !$topic->allow_text) ||
            ($validated['type'] === 'video' && !$topic->allow_video) ||
            ($validated['type'] === 'voice' && !$topic->allow_voice))
        ) {
            return response()->json(['message' => 'This reaction type is not allowed in this topic.'], 403);
        }
        $reaction = $post->reactions()->create([
            'id' => (string) Str::uuid(),
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'content' => $validated['content'],
        ]);

        $post->increment('reactions_count');

        return response()->json($reaction, 201);
    }

    public function storeReply(Request $request, Reaction $reaction)
    {
        $validated = $request->validate([
            'type' => 'required|in:text,video,voice',
            'content' => 'required|string',
        ]);

        $topic = $reaction->post->topic;

        if (
            ($validated['type'] === 'text' && !$topic->allow_text) ||
            ($validated['type'] === 'video' && !$topic->allow_video) ||
            ($validated['type'] === 'voice' && !$topic->allow_voice)
        ) {
            return response()->json(['message' => 'This reaction type is not allowed in this topic.'], 403);
        }

        $reply = Reaction::create([
            'id' => (string) Str::uuid(),
            'post_id' => $reaction->post_id,
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'content' => $validated['content'],
            'parent_id' => $reaction->id, // ✅ this is the key for nesting
        ]);

        return response()->json($reply->load('user'), 201);
    }

    public function userReactions(User $user, Request $request)
    {
        // Authenticate if needed
        if (!Auth::check() && $request->bearerToken()) {
            $token = PersonalAccessToken::findToken($request->bearerToken());
            if ($token) {
                Auth::setUser($token->tokenable);
            }
        }

        $userId = Auth::id();

        $likedReactionIds = [];
        if ($userId) {
            $likedReactionIds = Like::where('user_id', $userId)
                ->where('likeable_type', Reaction::class)
                ->pluck('likeable_id')
                ->toArray();
        }

        $reactions = $user->reactions()
            ->with(['user', 'post.user', 'post.topic'])
            ->latest()
            ->paginate(10);

        return response()->json(
            $reactions->through(function ($reaction) use ($likedReactionIds) {
                return $reaction
                    ->makeHidden('user_id')
                    ->setAttribute('is_liked', in_array($reaction->id, $likedReactionIds));
            })
        );
    }


    public function destroy(Reaction $reaction)
    {
        if (Auth::id() !== $reaction->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $reaction->delete();

        return response()->json(null, 204);
    }
}

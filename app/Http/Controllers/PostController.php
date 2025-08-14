<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $topicId = $request->query('topic_id');
        if (!Auth::check() && $request->bearerToken()) {
            $token = PersonalAccessToken::findToken($request->bearerToken());
            if ($token) {
                Auth::setUser($token->tokenable);
            }
        }

        $userId = Auth::id();

        if (filled($topicId) && strtolower($topicId) !== 'null') {
            $topic = Topic::find($topicId);
            if (!$topic) return response()->json(['message' => 'Topic not found'], 404);
            if ($topic->visibility === 'private' && $userId !== $topic->created_by) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            $posts = $topic->posts()->with('user')->latest()->paginate(10);
        } else {
            $postsQuery = Post::with('user')->whereHas('topic', function ($query) {
                $query->where('visibility', 'public');
                if (Auth::check()) {
                    $query->orWhere(function ($subQuery) {
                        $subQuery->where('visibility', 'private')
                            ->where('created_by', Auth::id());
                    });
                }
            });

            $posts = $postsQuery->paginate(10);
//            $posts = $postsQuery->inRandomOrder()->paginate(10);
        }

        $likedPostIds = [];
        if ($userId) {
            $likedPostIds = Like::where('user_id', $userId)
                ->where('likeable_type', Post::class)
                ->pluck('likeable_id')
                ->toArray();
        }

        return response()->json(
            $posts->through(function ($post) use ($likedPostIds) {
                $post->user->setAttribute('is_followed', $post->user->is_followed);
                return $post
                    ->makeHidden('user_id')
                    ->setAttribute('is_liked', in_array($post->id, $likedPostIds));
            })
        );
    }

    public function getPostById(Request $request, $id)
    {
        if (!Auth::check() && $request->bearerToken()) {
            $token = PersonalAccessToken::findToken($request->bearerToken());
            if ($token) {
                Auth::setUser($token->tokenable);
            }
        }

        $userId = Auth::id();

        $post = Post::with(['user', 'topic'])->find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        if ($post->topic->visibility === 'private' && $post->topic->created_by !== $userId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $isLiked = false;
        if ($userId) {
            $isLiked = Like::where('user_id', $userId)
                ->where('likeable_type', Post::class)
                ->where('likeable_id', $post->id)
                ->exists();
        }

        $post->user->setAttribute('is_followed', $post->user->is_followed);

        return response()->json(
            $post->makeHidden('user_id')->setAttribute('is_liked', $isLiked)
        );
    }



    public function upload(Request $request)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $directory = $request->post('directory');

            // Sanitize and default the directory if needed
            $directory = trim($directory ?? 'default');
            $directory = str_replace(['..', './', '\\'], '', $directory); // optional security step

            // Store the file in a subdirectory of "uploads" (e.g., uploads/images/)
            $path = $file->store("uploads/{$directory}", 'public');

            return response()->json(['url' => Storage::url($path)]);
        }

        return response()->json(['error' => 'No file uploaded.'], 400);
    }


    public function store(Request $request, Topic $topic)
    {
        if ($topic->visibility === 'private' && Auth::id() !== $topic->created_by) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $validated = $request->validate([
            'content' => 'nullable|string',
            'media_url' => 'required_without:content|string',
            'type' => 'required|in:text,video,voice',
        ]);
        $post = $topic->posts()->create([
            'id' => Str::uuid(),
            'user_id' => Auth::id(),
            'content' => $validated['content'] ?? null,
            'media_url' => $validated['media_url'] ?? null,
            'type' => $validated['type'],
        ]);
        return response()->json($post, 201);
    }

    public function destroy(Request $request, Post $post)
    {
        \Log::info('Destroy method called', ['post_id' => $post->id]);
        // Authenticate via bearer token if not already authenticated
        if (!Auth::check() && $request->bearerToken()) {
            $token = PersonalAccessToken::findToken($request->bearerToken());
            if ($token) {
                Auth::setUser($token->tokenable);
            }
        }

        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check if the authenticated user owns the post
        if (Auth::id() !== $post->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Delete the post
        $post->delete();

        return response()->json(['message' => 'Post deleted successfully'], 200);
    }


    public function userPosts(User $user, Request $request)
    {
        if (!Auth::check() && $request->bearerToken()) {
            $token = PersonalAccessToken::findToken($request->bearerToken());
            if ($token) {
                Auth::setUser($token->tokenable);
            }
        }

        $userId = Auth::id();

        $likedPostIds = [];
        if ($userId) {
            $likedPostIds = Like::where('user_id', $userId)
                ->where('likeable_type', Post::class)
                ->pluck('likeable_id')
                ->toArray();
        }

        $posts = $user->posts()->with('user')->latest()->paginate(10);

        return response()->json(
            $posts->through(function ($post) use ($likedPostIds) {
                $post->user->setAttribute('is_followed', $post->user->is_followed);
                return $post
                    ->makeHidden('user_id')
                    ->setAttribute('is_liked', in_array($post->id, $likedPostIds));
            })
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TopicController extends Controller
{
    public function index()
    {
        $topics = Topic::where('visibility', 'public')->get();
        return response()->json($topics);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:topics,name',
            'description' => 'required|string',
            'visibility' => 'required|in:public,private',
            'allow_text' => 'required|boolean',
            'allow_video' => 'required|boolean',
            'allow_voice' => 'required|boolean',
        ]);

        $topic = Topic::create([
            'id' => (string) Str::uuid(),
            'name' => $validated['name'],
            'description' => $validated['description'],
            'visibility' => $validated['visibility'],
            'allow_text' => $validated['allow_text'],
            'allow_video' => $validated['allow_video'],
            'allow_voice' => $validated['allow_voice'],
            'created_by' => Auth::id(),
        ]);

        return response()->json($topic, 201);
    }
}

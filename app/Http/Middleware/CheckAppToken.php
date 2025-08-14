<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAppToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-App-Token');

        if ($token !== config('app.app_token')) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
  public function handle(Request $request, Closure $next)
  {
    $apiKey = env('API_KEY');
    $authHeader = $request->header('Authorization');

    if (!$authHeader || $authHeader !== 'Bearer ' . $apiKey) {
      return response()->json(['message' => 'Unauthorized'], 401);
    }

    return $next($request);
  }
}

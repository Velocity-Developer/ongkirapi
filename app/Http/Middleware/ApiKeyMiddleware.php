<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
  public function handle(Request $request, Closure $next)
  {
    $apiKey = env('API_KEY');
    $v3Token = env('V3TOKEN');
    $authHeader = $request->header('key');
    $path = ltrim($request->getPathInfo(), '/');
    $isV3 = str_starts_with($path, 'api/v3/') || str_starts_with($path, 'v3/');
    if ($isV3) {
      if (!$authHeader || $authHeader !== $v3Token) {
        return response()->json(['message' => 'Unauthorized'], 401);
      }
    } else {
      if (!$authHeader || $authHeader !== $apiKey) {
        return response()->json(['message' => 'Unauthorized'], 401);
      }
    }
    return $next($request);
  }
}

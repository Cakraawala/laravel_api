<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->apikey;
        $apiKey = ApiKey::where('key', $key)->first();
        if ($key === null) {
            return response()->json(['Message' => 'Api Key Required'], 401);
        }
        if (!$apiKey) {
            return response()->json(['Message' => 'Invalid API key'], 401);
        }
        $rateLimit = 100;
        $timer = 60;
        $requests = Cache::get($key, 0);
        $lastRequestTime = Cache::get($key . ':timer');
        if ($lastRequestTime == null) {
            Cache::put($key, 0, now()->addMinute());
            $requests = 0;
            Cache::put($key . ':timer', time(), now()->addMinute());
        } else {
            if ($requests >= $rateLimit) {
                if (Cache::has($key . ':timer') && (time() - $lastRequestTime) > $timer) {
                    Cache::put($key, 0, now()->addMinute());
                    $requests = 0;
                } else {
                    return response()->json(['message' => 'Rate limit exceeded'], 429);
                }
            } else {
                Cache::increment($key);
            }

            Cache::put($key . ':timer', time(), now()->addMinute());
        }
        return $next($request);
    }
}

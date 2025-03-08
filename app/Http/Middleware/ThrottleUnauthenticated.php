<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ThrottleUnauthenticated
{
    protected $limit = 5;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $cacheKey = "throttle_unauthenticated_{$ip}";
        if (! $request->user()) {
            // Get the current request count
            $requestCount = Cache::get($cacheKey, 0);

            if ($requestCount >= $this->limit) {
                return response()->json(['message' => 'maximum number of request reached. Please login to continue'], 429);
            }

            // Increment request count with expiration time
            Cache::put($cacheKey, $requestCount + 1);
        } else {
            Cache::forget($cacheKey);

        }
        return $next($request);
    }
}

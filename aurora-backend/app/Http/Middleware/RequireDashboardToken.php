<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireDashboardToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = (string) config('aurora.dashboard_token');

        if ($expectedToken === '') {
            return $next($request);
        }

        $providedToken = (string) $request->header('X-Aurora-Dashboard-Token');

        if (! hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'message' => 'Dashboard access token is missing or invalid.',
            ], 401);
        }

        return $next($request);
    }
}

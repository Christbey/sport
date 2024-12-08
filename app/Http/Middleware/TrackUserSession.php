<?php

namespace App\Http\Middleware;

use App\Models\SessionCookie;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackUserSession
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response) $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Attempt to get unique_id from cookie
        $uniqueId = $request->cookie('unique_id');

        // If using localStorage on the frontend, you might pass it as a header or param:
        // $uniqueId = $request->header('X-Unique-Id') ?: $uniqueId;
        // or
        // $uniqueId = $request->input('unique_id') ?: $uniqueId;

        if (!$uniqueId) {
            $uniqueId = Str::random(13);
        }

        // Determine IP (v4 / v6)
        $ip = $request->ip();
        $ip_v4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $ip : null;
        $ip_v6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? $ip : null;

        $user = Auth::user();
        $userId = $user ? $user->id : null;

        // Find or create the session cookie record
        $sessionCookie = SessionCookie::firstOrCreate(
            ['unique_id' => $uniqueId],
            [
                'user_id' => $userId,
                'ip_v4' => $ip_v4,
                'ip_v6' => $ip_v6,
                'user_agent' => $request->userAgent(),
            ]
        );

        // Update fields if needed on each request
        $sessionCookie->user_id = $userId;
        $sessionCookie->ip_v4 = $ip_v4;
        $sessionCookie->ip_v6 = $ip_v6;
        $sessionCookie->user_agent = $request->userAgent();
        $sessionCookie->touch(); // updates updated_at
        $sessionCookie->save();

        // Proceed with the request and attach the cookie to the response
        $response = $next($request);
        if (!$request->cookie('unique_id')) {
            // Set cookie if it doesn't exist yet
            $response->headers->setCookie(cookie('unique_id', $uniqueId, 60 * 24 * 30)); // 30 days
        }

        return $response;
    }
}

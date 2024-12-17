<?php

namespace App\Http\Middleware;

use App\Models\SessionCookie;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class TrackUserSession
{
    private const FREE_LIMIT = 5;
    private const PREMIUM_LIMIT = 25;
    private const DECAY_MINUTES = 60; // 1 hour window

    public function handle(Request $request, Closure $next)
    {
        // Bypass middleware for Stripe and webhook routes
        if ($request->is('stripe/*') || $request->is('*/webhook')) {
            return $next($request);
        }

        // Retrieve or generate unique_id cookie
        $uniqueId = $request->cookie('unique_id') ?? Str::random(13);
        $user = Auth::user();
        $userId = $user?->id;

        // Create or update session record
        $this->updateSessionTracking($request, $uniqueId, $userId);

        // Determine rate limit based on subscription
        $limit = $user?->subscribed('default') ? self::PREMIUM_LIMIT : self::FREE_LIMIT;
        $key = $userId ? "chat:user:{$userId}" : "chat:session:{$uniqueId}";
        $rateLimitInfo = $this->getRateLimitInfo($key, $limit);

        // Share rate limit info with all views
        view()->share([
            'remainingRequests' => $rateLimitInfo['remaining_requests'],
            'maxRequests' => $rateLimitInfo['max_requests'],
            'resetTime' => $rateLimitInfo['seconds_until_reset']
        ]);

        // Handle rate limiting for POST requests to 'ask-chatgpt'
        if ($request->is('ask-chatgpt') && $request->isMethod('post')) {
            if (RateLimiter::tooManyAttempts($key, $limit)) {
                if (!$user?->subscribed('default')) {
                    return redirect()->route('subscription.index')
                        ->with('warning', 'You have reached your free message limit. Please upgrade to continue.');
                }

                return response()->json([
                    'error' => "Rate limit exceeded. Please try again in {$rateLimitInfo['seconds_until_reset']} seconds.",
                    'remaining_requests' => 0,
                    'max_requests' => $limit,
                    'seconds_until_reset' => $rateLimitInfo['seconds_until_reset']
                ], 429);
            }

            RateLimiter::hit($key, self::DECAY_MINUTES);
        }

        // Proceed with the request
        $response = $next($request);

        // Set unique_id cookie if it doesn't exist
        if (!$request->cookie('unique_id')) {
            $response->headers->setCookie(
                cookie('unique_id', $uniqueId, 60 * 24 * 30) // 30 days
            );
            // Add custom header for JavaScript to read
            $response->headers->set('X-Unique-ID', $uniqueId);
        }

        return $response;
    }

    /**
     * Create or update session tracking in the database.
     *
     * @param Request $request
     * @param string $uniqueId
     * @param int|null $userId
     * @return void
     */
    private function updateSessionTracking(Request $request, string $uniqueId, ?int $userId): void
    {
        $ip = $request->ip();

        SessionCookie::updateOrCreate(
            ['unique_id' => $uniqueId],
            [
                'user_id' => $userId,
                'ip_v4' => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $ip : null,
                'ip_v6' => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? $ip : null,
                'user_agent' => $request->userAgent(),
            ]
        );
    }

    /**
     * Retrieve rate limit information.
     *
     * @param string $key
     * @param int $limit
     * @return array
     */
    private function getRateLimitInfo(string $key, int $limit): array
    {
        return [
            'remaining_requests' => RateLimiter::remaining($key, $limit),
            'max_requests' => $limit,
            'seconds_until_reset' => RateLimiter::availableIn($key)
        ];
    }
}

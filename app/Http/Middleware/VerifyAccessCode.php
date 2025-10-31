<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class VerifyAccessCode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate key for rate limiting based on IP
        $key = 'verify_access_code_' . $request->ip();
        
        // Check if user is blocked for 1 day after 3 failed attempts
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            
            // Check if request expects JSON response (AJAX request)
            if ($request->expectsJson() || $request->isJson()) {
                return response()->json([
                    'error' => 'Akses ditangguhkan. Silakan coba lagi dalam ' . ceil($seconds/60) . ' menit.',
                    'blocked_until' => now()->addSeconds($seconds)->toISOString()
                ], 429);
            }
            
            return response('Akses ditangguhkan. Silakan coba lagi dalam ' . $seconds . ' detik.', 429);
        }

        // Check if user has been authenticated with access code
        if (!Session::has('access_code_verified') || !Session::get('access_code_verified')) {
            // Check if request expects JSON response (AJAX request)
            if ($request->expectsJson() || $request->isJson()) {
                return response()->json([
                    'error' => 'Akses tidak sah. Silakan verifikasi kode terlebih dahulu.',
                    'redirect' => route('verify.access.code')
                ], 401);
            }
            
            // Redirect to verification page if not verified
            return redirect()->route('verify.access.code');
        }

        return $next($request);
    }
}

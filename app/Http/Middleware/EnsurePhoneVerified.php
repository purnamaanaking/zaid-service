<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->phone_verified_at === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Phone number not verified.',
                'error' => [
                    'code' => 'PHONE_NOT_VERIFIED',
                    'details' => null,
                ],
            ], 403);
        }

        return $next($request);
    }
}

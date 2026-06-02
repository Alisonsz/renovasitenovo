<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Allow the request only for authenticated administrators.
     *
     * The /admin area was previously guarded by `auth` alone, which would let
     * any authenticated user (including future store customers) reach it.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if($user === null, 403);
        abort_unless((bool) ($user->is_admin ?? false), 403);

        return $next($request);
    }
}

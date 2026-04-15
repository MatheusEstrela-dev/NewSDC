<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        \Illuminate\Support\Facades\Log::warning('User unauthenticated on path: ' . $request->path() . ' expectsJson: ' . ($request->expectsJson() ? 'true' : 'false'));
        return $request->expectsJson() ? null : route('login');
    }
}

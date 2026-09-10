<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectInsecureTestHosts
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->secure() && str_ends_with($request->getHost(), '.test')) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}

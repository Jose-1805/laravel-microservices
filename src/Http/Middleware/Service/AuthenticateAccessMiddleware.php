<?php

namespace Jose1805\LaravelMicroservices\Http\Middleware\Service;

use Closure;
use Illuminate\Http\Response;

class AuthenticateAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $validSecrets = explode(',', config('microservices.access_tokens'));
        if (in_array($request->header('Authorization'), $validSecrets)) {
            return $next($request);
        }

        abort(Response::HTTP_UNAUTHORIZED);
    }
}

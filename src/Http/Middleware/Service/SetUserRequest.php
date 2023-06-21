<?php

namespace Jose1805\LaravelMicroservices\Http\Middleware\Service;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetUserRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->header('UserId')) {
            $user = new User();
            $user->id = $request->header('UserId');

            $request->setUserResolver(function () use ($user) {
                return $user;
            });
        }
        return $next($request);
    }
}

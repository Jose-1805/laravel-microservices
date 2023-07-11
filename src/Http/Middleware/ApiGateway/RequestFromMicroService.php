<?php

namespace Jose1805\LaravelMicroservices\Http\Middleware\ApiGateway;

use Illuminate\Support\Facades\Config;
use Jose1805\LaravelMicroservices\Models\Service;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Response as HttpResponse;

class RequestFromMicroService
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(!Config::get('request_from_micro_service')) {
            abort(HttpResponse::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}

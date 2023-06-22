<?php

namespace Jose1805\LaravelMicroservices\Http\Middleware\ApiGateway;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Jose1805\LaravelMicroservices\Traits\Teams;
use Symfony\Component\HttpFoundation\Response;

class TeamsPermission
{
    use Teams;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(!empty(auth()->user())) {
            $team_id = $request->headers->get('Team-Id');

            //Siempre se requiere el nombre del equipo
            if(!$team_id) {
                abort(HttpResponse::HTTP_UNAUTHORIZED);
            }

            setPermissionsTeamId($team_id);
        }

        return $next($request);
    }
}

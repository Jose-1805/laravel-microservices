<?php

namespace Jose1805\LaravelMicroservices\Traits;

use App\Models\User;

trait Teams
{
    /**
     * Obtiene array con la lista de equipos a los que pertenece el usuario
     *
     * @param string $user_id
     * @return array
     */
    public function getTeams(string $user_id): array
    {
        return User::select('model_has_roles.team_id')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->where('model_has_roles.model_type', User::class)
            ->where('model_has_roles.model_id', $user_id)
            ->groupBy('model_has_roles.team_id')->get()
            ->map(fn ($team) => ['team_id' => $team->team_id])
            ->all();
    }
}

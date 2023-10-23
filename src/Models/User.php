<?php

namespace Jose1805\LaravelMicroservices\Models;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable as AccessAuthorizable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Model implements Authorizable
{
    use HasFactory;
    use HasApiTokens;
    use HasRoles;
    use AccessAuthorizable;

    /**
     * Relación a las solicitudes en segundo plano
     */
    public function backgroundRequest()
    {
        return $this->hasMany(BackgroundRequest::class);
    }

    /**
     * Información completa del usuario
     *
     * @return void
     */
    public function allData(): User
    {
        $this->role_data = $this->roles()->select('roles.id', 'roles.name')->with('permissions:id,name')->get();
        $this->permissions;
        return $this;
    }
}

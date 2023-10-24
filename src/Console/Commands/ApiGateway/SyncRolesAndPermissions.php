<?php

namespace Jose1805\LaravelMicroservices\Console\Commands\ApiGateway;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SyncRolesAndPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lm:sync-roles-and-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza de roles y permisos en el sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Array para almacenar todos los objetos de permisos
        $permissions = [];
        $this->info("Consultando y/o creando permisos.");

        // Consulta y/o creación de permisos
        foreach(config('laravel_microservices.roles.permissions') as $permissionName) {
            $permissions[$permissionName] = Permission::findOrCreate($permissionName);
        }

        $this->info("Sincronizando roles con los permisos encontrados");
        $this->info("");
        $this->info("");
        // Sincronización de roles y permisos
        foreach(config('laravel_microservices.roles.roles') as $roleName => $data) {
            $this->info("-------");
            $this->info("Procesando el rol ($roleName)");
            $role = null;
            if($data['team']) {
                setPermissionsTeamId($data['team']);
                $role = Role::findOrCreate($roleName);
            } else {
                $role = Role::findOrCreate($roleName);
            }

            $this->info("ROLE ID: " . $role->id);

            $permissionList = $data['permissions'] ?? [];

            // Revisión individual de permisos asociados al role
            // el role debe existir en la lista de objetos roles $permissions
            foreach($permissionList as $permissionName) {
                $permission = $permissions[$permissionName] ?? null;
                if($permission) {
                    $this->info("Revisando permiso: Nombre - " . $permission->name . " | id - " . $permission->id);

                    if(!$role->hasPermissionTo($permission)) {
                        $this->info("Asignando el permiso");
                        $role->givePermissionTo($permission);
                    } else {
                        $this->info("El permiso ya está asignado");
                    }
                }
            }
            $this->info("");
            $role = null;
        }

        $this->info("Roles y permisos sincronizados con éxito");
    }
}

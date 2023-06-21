<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    // Lista de permisos definidos por cada módulo
    // Borre y agregue los módulos y permisos de su sistema
    protected $permissions = [
        'users' => ['view-users', 'create-users', 'update-users', 'delete-users'],
        'roles' => ['view-roles', 'create-roles', 'update-roles', 'delete-roles'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->insertPermissions();
        $this->makeSuperAdminRole();
        $this->makeOtherRole();
    }

    /**
     * Inserta todos los permisos en la base de datos
     *
     * @return void
     */
    protected function insertPermissions(): void
    {
        $arrayOfPermissionNames = array_unique(array_merge(...array_values($this->permissions)));

        $permissions = collect($arrayOfPermissionNames)->map(function ($permission) {
            return ['name' => $permission, 'guard_name' => 'web'];
        });

        Permission::insert($permissions->toArray());
    }

    /**
     * Crea la configuración de rol de super administrador
     *
     * @return void
     */
    protected function makeSuperAdminRole(): void
    {
        $role = Role::create(['name' => 'super-admin', 'team_id' => 'default-team']);
        $admin_permissions = array_merge(
            $this->permissions['users'],
            $this->permissions['roles'],
        );
        $role->givePermissionTo($admin_permissions);
    }

    /**
     * Crea la configuración de rol de ...
     *
     * @return void
     */
    protected function makeOtherRole(): void
    {
        $role = Role::create(['name' => 'other-role', 'team_id' => null]);
        $role_permissions = array_merge(
            $this->permissions['users'],
            $this->permissions['roles'],
        );
        $role->givePermissionTo($role_permissions);
    }
}

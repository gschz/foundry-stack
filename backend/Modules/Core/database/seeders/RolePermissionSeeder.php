<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder para crear los roles y permisos del sistema.
 */
final class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Iniciando seeder de Roles y Permisos...');

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // CREAR PERMISOS - Solo los esenciales para acceso a módulos
        $permissions = [
            'access-module-01',
            'access-module-02',
            'access-admin',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'staff',
            ]);
        }

        $this->command->info('Permisos creados.');

        // CREAR ROLES Y ASIGNAR PERMISOS
        // Rol: ADMIN (super-admin con todos los permisos)
        $roleAdmin = Role::query()->firstOrCreate([
            'name' => 'ADMIN',
            'guard_name' => 'staff',
        ]);
        $roleAdmin->givePermissionTo($permissions);

        // Rol: DEV (también es super-admin con todos los permisos)
        $roleDev = Role::query()->firstOrCreate([
            'name' => 'DEV',
            'guard_name' => 'staff',
        ]);
        $roleDev->givePermissionTo($permissions);

        // Roles de Módulos (MOD-XX) - cada uno solo con su permiso principal
        Role::query()->firstOrCreate([
            'name' => 'MOD-01',
            'guard_name' => 'staff',
        ])->givePermissionTo('access-module-01');

        Role::query()->firstOrCreate([
            'name' => 'MOD-02',
            'guard_name' => 'staff',
        ])->givePermissionTo('access-module-02');

        // NOTA: El usuario administrador base (ADMIN) es creado en SystemUsersSeeder
        // después de que estos roles y permisos han sido establecidos.

        // Registrar información en el log
        Log::info('Seeder de roles y permisos ejecutado:', [
            'roles_count' => Role::query()->count(),
            'permissions_count' => Permission::query()->count(),
            'roles' => Role::all(['id', 'name', 'guard_name'])->toArray(),
        ]);

        $this->command->info('Seeder de roles y permisos completado.');
    }
}

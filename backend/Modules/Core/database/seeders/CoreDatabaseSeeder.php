<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

final class CoreDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            // TestStaffUsersSeeder::class, # Descomenta para ejecutar automáticamente, o 'bun run be artisan module:seed Core --class=TestStaffUsersSeeder'
        ]);

        $this->command->info('Sincronizando permisos entre guards...');
        Artisan::call('permissions:sync-guards');
    }
}

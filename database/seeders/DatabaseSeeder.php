<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Ejecutar con Bun desde la raíz del proyecto:
 * - bun run be artisan db:seed # Seed de todos los seeders definidos en esta clase.
 * - bun run be artisan module:seed Core # Seed de todos los seeders definidos en el módulo.
 * - bun run be artisan module:seed Core --class=CoreDatabaseSeeder # Seed para un seeder específico del módulo.
 *
 * Nota: Usa 'bun run be artisan' o 'bun run be pg' como script base 
 * para ejecutar cualquier comando de Artisan evitando duplicar scripts.
 */

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            \Modules\Core\Database\Seeders\CoreDatabaseSeeder::class,
            // \Modules\Module01\Database\Seeders\Module01DatabaseSeeder::class,
            // \Modules\Module02\Database\Seeders\Module02DatabaseSeeder::class,
        ]);
    }
}

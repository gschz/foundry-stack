<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

final class SyncGuardPermissionsCommand extends Command
{
    /**
     * El nombre y la firma del comando de consola.
     *
     * @var string
     */
    protected $signature = 'permissions:sync-guards';

    /**
     * La descripción del comando de consola.
     *
     * @var string
     */
    protected $description = 'Sincroniza roles y permisos entre los guards web y sanctum';

    /**
     * Ejecuta el comando de consola.
     */
    public function handle(): int
    {
        $this->info('Iniciando sincronización de roles y permisos entre guards...');

        try {
            // Usar el método estático del trait CrossGuardPermissions
            StaffUser::syncPermissionsBetweenGuards();

            $this->info('Sincronización completada exitosamente.');

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $this->error('Error durante la sincronización: '.$exception->getMessage());

            return Command::FAILURE;
        }
    }
}

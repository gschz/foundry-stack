<?php

declare(strict_types=1);

namespace Modules\Core\Application\Permissions;

use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Caso de uso: sincronizar permisos/roles entre guards.
 */
final readonly class SyncCrossGuardPermissions
{
    /**
     * Sincroniza permisos/roles (web ↔ sanctum) usando el trait del modelo StaffUser.
     */
    public function handle(): void
    {
        StaffUser::syncPermissionsBetweenGuards();
    }
}

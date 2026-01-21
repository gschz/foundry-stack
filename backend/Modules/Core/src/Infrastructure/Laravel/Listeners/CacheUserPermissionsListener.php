<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Listeners;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Core\Infrastructure\Laravel\Services\PermissionService;

/**
 * Listener para invalidar la caché de permisos cuando estos cambian.
 */
final readonly class CacheUserPermissionsListener
{
    /**
     * @param  PermissionService  $permissionService  Servicio para limpiar caché de permisos del usuario.
     */
    public function __construct(
        private PermissionService $permissionService
    ) {
        //
    }

    /**
     * Maneja eventos relacionados con cambios de permisos/roles.
     *
     * @param  object  $event  Evento que porta un usuario autenticable (si aplica).
     */
    public function handle(object $event): void
    {
        // Detectar si el evento tiene un usuario asociado
        if (isset($event->user) && $event->user instanceof Authenticatable) {
            $this->permissionService->clearCache($event->user);
        }
    }
}

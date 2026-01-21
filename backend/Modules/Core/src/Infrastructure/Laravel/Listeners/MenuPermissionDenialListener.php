<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Core\Infrastructure\Laravel\Events\MenuPermissionDenied;

/**
 * Listener para centralizar logs de denegaciones de navegación por permisos.
 */
final readonly class MenuPermissionDenialListener
{
    /**
     * Maneja el evento de denegación de permiso en navegación.
     */
    public function handle(MenuPermissionDenied $event): void
    {
        Log::channel('domain_navigation')->info('permission_denied_event', [
            'permission' => $event->permission,
            'module' => $event->moduleSlug,
            'context' => $event->context,
            'user_id' => $event->user?->getAuthIdentifier(),
        ]);
    }
}

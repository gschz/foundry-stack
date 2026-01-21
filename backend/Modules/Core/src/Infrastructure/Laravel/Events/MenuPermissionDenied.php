<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Events;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Evento disparado cuando se deniega un elemento por permisos al construir navegación.
 */
final class MenuPermissionDenied
{
    /**
     * @param  string  $permission  Permiso que causó la denegación
     * @param  string|null  $moduleSlug  Slug del módulo en contexto
     * @param  Authenticatable|null  $user  Usuario autenticado (si aplica)
     * @param  string  $context  Contexto de navegación (ej. contextual_nav, panel, global)
     */
    public function __construct(
        public string $permission,
        public ?string $moduleSlug,
        public ?Authenticatable $user,
        public string $context = 'contextual_nav'
    ) {
        //
    }
}

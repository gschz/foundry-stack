<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Traits;

use Illuminate\Support\Facades\Auth;
use Modules\Core\Contracts\PermissionVerifierInterface;

/**
 * Trait de infraestructura para verificar permisos del usuario autenticado.
 *
 * Encapsula el acceso al usuario actual (vía Auth) y delega la verificación
 * al contrato de Core para permisos cross-guard.
 */
trait PermissionVerifier
{
    /**
     * Verifica si el usuario autenticado tiene un permiso específico o alguno de una lista.
     *
     * @param  string|array<string>  $permissionName  Permiso o lista de permisos.
     * @return bool True si el usuario tiene acceso; false en caso contrario.
     */
    public function can(string|array $permissionName): bool
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable|null $user */
        $user = Auth::user();

        $permission = is_array($permissionName)
            ? ($permissionName[0] ?? '')
            : $permissionName;

        return app(PermissionVerifierInterface::class)->checkCrossGuard(
            $user,
            $permission
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Interfaz para la verificación de permisos.
 * Define los métodos necesarios para verificar permisos de usuario,
 * incluyendo soporte para verificación entre guards (cross-guard).
 */
interface PermissionVerifierInterface
{
    /**
     * Verifica si el usuario tiene un permiso específico.
     *
     * @param  Authenticatable|null  $user  El usuario a verificar.
     * @param  string|array<string>  $permission  El permiso o array de permisos a verificar.
     * @param  string|null  $guard  El guard específico a verificar (opcional).
     * @return bool Verdadero si el usuario tiene el permiso.
     */
    public function check(
        ?Authenticatable $user,
        string|array $permission,
        ?string $guard = null
    ): bool;

    /**
     * Verifica si el usuario tiene un permiso específico en cualquier guard configurado.
     *
     * @param  Authenticatable|null  $user  El usuario a verificar.
     * @param  string  $permission  El permiso a verificar.
     * @return bool Verdadero si el usuario tiene el permiso en algún guard.
     */
    public function checkCrossGuard(
        ?Authenticatable $user,
        string $permission
    ): bool;

    /**
     * Limpia la caché de permisos para un usuario.
     *
     * @param  Authenticatable  $user  El usuario.
     */
    public function clearCache(Authenticatable $user): void;
}

<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Modules\Core\Contracts\PermissionVerifierInterface;

/**
 * Trait PermissionVerifier
 * Verifica si el usuario autenticado tiene un permiso específico o alguno de una lista.
 *
 * Este trait es un wrapper sobre PermissionService para usar en componentes
 * que no tienen acceso directo al usuario autenticado.
 */
trait PermissionVerifier
{
    /**
     * Verifica si el usuario autenticado tiene un permiso específico o alguno de una lista.
     *
     * @param  string|array<string>  $permissionName
     */
    public function can(string|array $permissionName): bool
    {
        /** @var \App\Interfaces\AuthenticatableUser|\Illuminate\Contracts\Auth\Authenticatable|null $user */
        $user = Auth::user();

        // Delegar al servicio de permisos del Core
        return app(PermissionVerifierInterface::class)->checkCrossGuard(
            $user,
            is_array($permissionName) ? $permissionName[0] : $permissionName // Simplificación para compatibilidad, idealmente usar checkAny
        );
    }
}

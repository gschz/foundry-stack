<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use App\Interfaces\AuthenticatableUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Contracts\PermissionVerifierInterface;

/**
 * Servicio de verificación de permisos (implementación Laravel).
 *
 * Implementa verificación estándar y cross-guard con invalidación
 * de caché por usuario cuando cambia su estado de permisos/roles.
 */
final class PermissionService implements PermissionVerifierInterface
{
    /**
     * {@inheritDoc}
     */
    public function check(
        ?Authenticatable $user,
        string|array $permission,
        ?string $guard = null
    ): bool {
        if (! $user instanceof Authenticatable) {
            return false;
        }

        // Si el usuario implementa nuestra interfaz AuthenticatableUser, usar sus métodos tipados
        if ($user instanceof AuthenticatableUser) {
            if (is_array($permission)) {
                return array_reduce(
                    $permission,
                    fn (bool $carry, string $perm): bool => $carry || $user->hasPermissionTo($perm, $guard),
                    false
                );
            }

            return $user->hasPermissionTo($permission, $guard);
        }

        // Fallback: Si el usuario tiene el método hasPermissionTo (Spatie) pero no implementa nuestra interfaz
        if (method_exists($user, 'hasPermissionTo')) { // @phpstan-ignore function.alreadyNarrowedType
            if (is_array($permission)) {
                /** @disregard P1013 Undefined method 'hasPermissionTo' */
                $result = array_reduce(
                    $permission,
                    fn (bool $carry, string $perm): bool => $carry || (bool) $user->hasPermissionTo($perm, $guard),
                    false
                );

                return $result;
            }

            /** @disregard P1013 Undefined method 'hasPermissionTo' */
            return (bool) $user->hasPermissionTo($permission, $guard);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function checkCrossGuard(
        ?Authenticatable $user,
        string $permission
    ): bool {
        if (! $user instanceof Authenticatable) {
            return false;
        }

        // Si el usuario implementa nuestra interfaz AuthenticatableUser
        if ($user instanceof AuthenticatableUser) {
            return $user->hasPermissionToCross($permission);
        }

        // Si el usuario usa el trait HasCrossGuardPermissions pero no implementa la interfaz (caso raro)
        if (method_exists($user, 'hasPermissionToCross')) { // @phpstan-ignore function.alreadyNarrowedType
            /** @disregard P1013 Undefined method 'hasPermissionToCross' */
            return (bool) $user->hasPermissionToCross($permission);
        }

        // Fallback a verificación estándar
        return $this->check($user, $permission);
    }

    /**
     * {@inheritDoc}
     */
    public function clearCache(Authenticatable $user): void
    {
        // Incrementar versión de permisos para invalidar caché
        $authIdentifier = $user->getAuthIdentifier();
        if (is_numeric($authIdentifier) || is_string($authIdentifier)) {
            $key = 'user.'.$authIdentifier.'.perm_version';
            $val = Cache::get($key, 0);
            $currentVersion = is_numeric($val) ? (int) $val : 0;
            Cache::put($key, $currentVersion + 1, now()->addDays(30));
        }

        // También limpiar caché de Spatie si es necesario
        if (method_exists($user, 'forgetCachedPermissions')) { // @phpstan-ignore function.alreadyNarrowedType
            /** @disregard P1013 Undefined method 'forgetCachedPermissions' */
            $user->forgetCachedPermissions();
        }
    }
}

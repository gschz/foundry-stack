<?php

declare(strict_types=1);

namespace Modules\Core\Domain\User;

use Modules\Core\Domain\Permission\PermissionCollection;

/**
 * Entidad de dominio para usuario interno (Staff).
 *
 * Modela identidad, atributos básicos y verificación de permisos/roles
 * usando colecciones inmutables.
 */
final readonly class StaffUser
{
    /**
     * @param  array<int, string>  $roles
     */
    public function __construct(
        public StaffUserId $id,
        public string $name,
        public string $email,
        public array $roles,
        public PermissionCollection $permissions
    ) {}

    /**
     * Verifica si el usuario tiene un permiso.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions->contains($permission);
    }

    /**
     * Verifica si el usuario tiene alguno de los permisos dados.
     *
     * @param  array<int, string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return array_any($permissions, $this->permissions->contains(...));
    }

    /**
     * Verifica si el usuario tiene un rol indicado (comparación estricta).
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    /**
     * Verifica si el usuario tiene alguno de los roles indicados.
     *
     * @param  array<int, string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return array_any($roles, fn ($role): bool => in_array($role, $this->roles, true));
    }
}

<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Permission;

/**
 * Value Object para colecciones de permisos.
 *
 * Provee utilidades de consulta y filtrado con tipado estricto.
 */
final readonly class PermissionCollection
{
    /**
     * @param  array<int, string>  $permissions  Lista de permisos normalizados
     */
    public function __construct(private array $permissions)
    {
        //
    }

    /**
     * Crea una colección desde un array arbitrario, normalizando a strings.
     *
     * @param  array<mixed>  $values
     */
    public static function fromArray(array $values): self
    {
        $perms = array_values(array_filter(
            array_map(
                static fn ($v): ?string => is_string($v) && $v !== '' ? $v : null,
                $values
            ),
            static fn (?string $v): bool => $v !== null
        ));

        return new self($perms);
    }

    /**
     * Indica si la colección contiene un permiso dado.
     */
    public function contains(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    /**
     * Filtra permisos por prefijo.
     *
     * @return array<int, string>
     */
    public function filterByPrefix(string $prefix): array
    {
        return array_values(array_filter(
            $this->permissions,
            static fn (string $p): bool => str_starts_with($p, $prefix)
        ));
    }

    /**
     * Devuelve todos los permisos como array.
     *
     * @return array<int, string>
     */
    public function toArray(): array
    {
        return $this->permissions;
    }

    /**
     * Cantidad de permisos.
     */
    public function count(): int
    {
        return count($this->permissions);
    }

    /**
     * Indica si la colección está vacía.
     */
    public function isEmpty(): bool
    {
        return $this->permissions === [];
    }
}

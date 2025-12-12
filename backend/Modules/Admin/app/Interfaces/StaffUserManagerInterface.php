<?php

declare(strict_types=1);

namespace Modules\Admin\App\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;
use Spatie\Permission\Models\Role;

/**
 * Interfaz para la gestión de usuarios del personal administrativo.
 * Define las operaciones esenciales para administrar usuarios staff en el sistema.
 */
interface StaffUserManagerInterface
{
    /**
     * Parámetros de ordenación permitidos.
     */
    public const ALLOWED_SORT_FIELDS = [
        'id',
        'name',
        'email',
        'created_at',
        'updated_at',
    ];

    /**
     * Obtiene una lista paginada de todos los usuarios con sus roles.
     *
     * @param  array<string, mixed>  $params  Parámetros para filtrado y ordenación, incluyendo:
     *                                        - search: Término de búsqueda
     *                                        - role: Filtrado por nombre de rol
     *                                        - sort_field: Campo para ordenar resultados
     *                                        - sort_direction: Dirección de ordenamiento (asc/desc)
     *                                        - per_page: Número de elementos por página
     * @param  int  $perPage  Número predeterminado de elementos por página
     * @return LengthAwarePaginator<array-key, StaffUser> Lista paginada de usuarios
     */
    public function getAllUsers(
        array $params = [],
        int $perPage = 10
    ): LengthAwarePaginator;

    /**
     * Crea un nuevo usuario con los datos proporcionados.
     *
     * @param  array<string, mixed>  $data  Datos del nuevo usuario (name, email, password, etc.)
     * @return StaffUser Usuario creado
     */
    public function createUser(array $data): StaffUser;

    /**
     * Obtiene un usuario por su ID.
     *
     * @param  int  $id  ID del usuario
     * @return StaffUser|null Usuario encontrado o null
     */
    public function getUserById(int $id): ?StaffUser;

    /**
     * Actualiza un usuario existente.
     *
     * @param  int  $id  ID del usuario
     * @param  array<string, mixed>  $data  Datos actualizados (name, email, etc.)
     * @return StaffUser|null Usuario actualizado o null
     */
    public function updateUser(int $id, array $data): ?StaffUser;

    /**
     * Elimina un usuario por su ID.
     *
     * @param  int  $id  ID del usuario
     * @return bool Éxito de la operación
     */
    public function deleteUser(int $id): bool;

    /**
     * Sincroniza los roles de un usuario, preservando los roles protegidos.
     * Los roles protegidos (ADMIN y DEV) no pueden ser eliminados si ya están asignados.
     *
     * @param  StaffUser  $user  Usuario a actualizar
     * @param  array<string|int|Role>  $roles  Roles a asignar, pueden ser nombres de rol, IDs o instancias de Role
     */
    public function syncRoles(StaffUser $user, array $roles): void;

    /**
     * Obtiene el número total de usuarios registrados.
     *
     * @return int Total de usuarios
     */
    public function getTotalUsers(): int;

    /**
     * Obtiene el número total de roles definidos.
     *
     * @return int Total de roles
     */
    public function getTotalRoles(): int;

    /**
     * Obtiene todos los roles disponibles en el sistema.
     *
     * @return Collection<int, Role> Colección de roles
     */
    public function getAllRoles(): Collection;
}

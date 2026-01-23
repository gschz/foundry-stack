<?php

declare(strict_types=1);

namespace Modules\Admin\App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Modules\Admin\App\Interfaces\StaffUserManagerInterface;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Servicio para manejar la lógica de negocio de la gestión de usuarios del personal (Staff).
 * Implementa las operaciones definidas en la interfaz StaffUserManagerInterface.
 */
final class AdminStaffUserService implements StaffUserManagerInterface
{
    /**
     * {@inheritDoc}
     */
    public function getAllUsers(
        array $params = [],
        int $perPage = 10
    ): LengthAwarePaginator {
        $query = StaffUser::query()
            // Eliminamos 'avatar' del select porque es un atributo computado
            ->select('id', 'name', 'email', 'created_at', 'updated_at')
            // Ya no existe la relación contactProfile
            ->with(['roles']);

        // Filtrado por término de búsqueda
        if (is_string($params['search'] ?? null) && $params['search'] !== '') {
            $searchTerm = $params['search'];
            $query->where(
                function ($q) use ($searchTerm): void {
                    $q->where('name', 'like', sprintf('%%%s%%', $searchTerm))
                        ->orWhere('email', 'like', sprintf('%%%s%%', $searchTerm));
                }
            );
        }

        // Filtrado por rol específico
        if (! empty($params['role'])) {
            $query->whereHas(
                'roles',
                function ($q) use ($params): void {
                    $q->where('name', $params['role']);
                }
            );
        }

        // Ordenamiento
        $sortField = is_string($params['sort_field'] ?? null)
            ? $params['sort_field'] : 'created_at';
        $sortDirection = is_string($params['sort_direction'] ?? null)
            ? $params['sort_direction'] : 'desc';

        // Verificar que el campo de ordenamiento es válido usando la constante de la interfaz
        if (in_array($sortField, self::ALLOWED_SORT_FIELDS, true)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Obtener número de elementos por página, asegurando tipo entero
        $perPage = isset($params['per_page']) && is_numeric($params['per_page']) ? (int) $params['per_page'] : $perPage;

        // Paginar los resultados
        return $query->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     *
     * Efectos secundarios:
     * - Establece `email_verified_at` si `auto_verify_email` es verdadero (por defecto).
     * - Inicializa `password_changed_at` al momento de creación.
     * - Sincroniza roles si se proporcionan en `data['roles']`.
     */
    public function createUser(array $data): StaffUser
    {
        // Determinar si se debe verificar automáticamente el email (por defecto: true)
        $shouldAutoVerify = ! isset($data['auto_verify_email'])
            || $data['auto_verify_email'] === true;

        if ($shouldAutoVerify) {
            $data['email_verified_at'] = now();
        }

        // Eliminar el campo auto_verify_email antes de crear el usuario
        if (isset($data['auto_verify_email'])) {
            unset($data['auto_verify_email']);
        }

        // Crear el usuario con los datos proporcionados
        $user = StaffUser::query()->create($data);
        // Inicializar fecha de establecimiento de contraseña
        $user->forceFill([
            'password_changed_at' => now(),
        ])->save();

        // Si no se verificará automáticamente, enviar notificación de verificación
        if (! $shouldAutoVerify) {
            $user->sendEmailVerificationNotification();
        }

        // Asignar roles si se proporcionan
        if (! empty($data['roles']) && is_array($data['roles'])) {
            /** @var array<int, string|int|Role> $roles */
            $roles = $data['roles'];
            $this->syncRoles($user, $roles);
        }

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function getUserById(int $id): ?StaffUser
    {
        return StaffUser::with('roles', 'permissions')->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function updateUser(int $id, array $data): ?StaffUser
    {
        $user = StaffUser::query()->find($id);
        if ($user) {
            // Extraer password_changed_at si viene en payload y evitar mass assignment
            $pwdChangedAt = $data['password_changed_at'] ?? null;
            if (array_key_exists('password_changed_at', $data)) {
                unset($data['password_changed_at']);
            }

            $user->update($data);

            if ($pwdChangedAt !== null) {
                $user->forceFill([
                    'password_changed_at' => $pwdChangedAt,
                ])->save();
            }
        }

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteUser(int $id): bool
    {
        $user = StaffUser::query()->find($id);
        if ($user) {
            return (bool) $user->delete();
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function syncRoles(StaffUser $user, array $roles): void
    {
        // 1. Filtrar los roles 'ADMIN' y 'DEV' de la solicitud.
        // Normaliza roles a nombres o IDs y filtra ADMIN/DEV
        /** @var array<int, string|int> $assignableRoles */
        $assignableRoles = array_values(array_filter(array_map(
            static function (string|int|Role $role): string|int {
                // Enteros y cadenas pasan directamente
                if (is_int($role) || is_string($role)) {
                    return $role;
                }

                // En este punto, por el filtro previo, $role es Role
                return $role->name; // usar nombre para evitar colisiones
            },
            $roles
        ), static fn (string|int $roleName): bool => (string) $roleName !== '' &&
            ! in_array(
                mb_strtoupper((string) $roleName),
                ['ADMIN', 'DEV'],
                true
            )));

        // 2. Obtener los nombres de roles protegidos que el usuario ya tiene.
        /** @var array<int, string> $protectedRoles */
        $protectedRoles = array_values(array_filter(
            $user->roles
                ->pluck('name')
                ->all(),
            static fn ($name): bool => is_string($name) && in_array(
                mb_strtoupper($name),
                ['ADMIN', 'DEV'],
                true
            )
        ));

        // 3. Unir los roles asignables con los protegidos existentes.
        /** @var array<int, string|int> $finalRoles */
        $finalRoles = array_values(array_unique(
            array_merge($assignableRoles, $protectedRoles)
        ));

        $user->syncRoles($finalRoles);

        $registrar = app()->make(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        $rawVersion = Cache::get('user.'.$user->id.'.perm_version', 0);
        $currentVersion = is_int($rawVersion)
            ? $rawVersion
            : (is_numeric($rawVersion) ? (int) $rawVersion : 0);

        Cache::put(
            'user.'.$user->id.'.perm_version',
            $currentVersion + 1,
            now()->addDays(7)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalUsers(): int
    {
        return StaffUser::query()->count();
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalRoles(): int
    {
        return Role::query()->where('guard_name', 'staff')->count();
    }

    /**
     * {@inheritDoc}
     */
    public function getAllRoles(): Collection
    {
        // Usar el modelo Role directamente con where para obtener una Eloquent\Collection
        $roles = Role::query()->where('guard_name', 'staff')->get([
            'id',
            'name',
            'guard_name',
        ]);

        // Añadir descripción para cada rol
        $roles->each(
            function (Role $role): void {
                // Asegurarnos de que el ID sea un entero para evitar problemas de tipado en el frontend
                $role->id = (int) $role->id;

                // Obtener el nombre de forma segura y decidir la descripción
                $nameAttr = $role->getAttribute('name');
                $upperName = is_string($nameAttr) ? mb_strtoupper($nameAttr) : '';

                match ($upperName) {
                    'ADMIN' => $role->setAttribute(
                        'description',
                        'Acceso completo a todas las funciones del sistema'
                    ),
                    'DEV' => $role->setAttribute(
                        'description',
                        'Acceso de desarrollador con privilegios especiales'
                    ),
                    'MOD-01' => $role->setAttribute(
                        'description',
                        'Acceso al Módulo 01'
                    ),
                    'MOD-02' => $role->setAttribute(
                        'description',
                        'Acceso al Módulo 02'
                    ),
                    default => $role->setAttribute(
                        'description',
                        is_string($nameAttr) ? ('Rol de '.$nameAttr) : 'Rol'
                    ),
                };
            }
        );

        return $roles;
    }
}

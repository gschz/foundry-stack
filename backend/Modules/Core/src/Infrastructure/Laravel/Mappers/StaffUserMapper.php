<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Mappers;

use Modules\Core\Domain\Permission\PermissionCollection;
use Modules\Core\Domain\User\StaffUser as DomainStaffUser;
use Modules\Core\Domain\User\StaffUserId;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser as EloquentStaffUser;

/**
 * Mapper de infraestructura para convertir modelos Eloquent a entidades de dominio.
 */
final readonly class StaffUserMapper
{
    /**
     * Convierte un modelo Eloquent de usuario staff a la entidad de dominio.
     *
     * @param  EloquentStaffUser  $model  Modelo Eloquent
     */
    public static function toDomain(EloquentStaffUser $model): DomainStaffUser
    {
        $rawId = $model->getAuthIdentifier();
        $idStr = is_string($rawId)
            ? $rawId
            : (is_int($rawId)
                ? (string) $rawId
                : ''
            );

        $permissions = PermissionCollection::fromArray(
            $model->getAllCrossGuardPermissions()
        );

        $roles = array_values(array_filter(
            $model->roles->pluck('name')->all(),
            is_string(...)
        ));

        $nameVal = $model->getAttribute('name');
        $emailVal = $model->getAttribute('email');
        $name = is_string($nameVal) ? $nameVal : '';
        $email = is_string($emailVal) ? $emailVal : '';

        return new DomainStaffUser(
            id: StaffUserId::fromString($idStr),
            name: $name,
            email: $email,
            roles: $roles,
            permissions: $permissions
        );
    }
}

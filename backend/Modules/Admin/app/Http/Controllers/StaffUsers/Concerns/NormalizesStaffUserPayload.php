<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers\StaffUsers\Concerns;

use Illuminate\Http\Request as IlluminateRequest;
use Modules\Admin\App\Http\Requests\StaffUserRequest;

/**
 * Normaliza payloads y roles de usuarios staff para controladores.
 */
trait NormalizesStaffUserPayload
{
    /**
     * Construye el payload de creación con contraseña lista para persistencia.
     *
     * @param  StaffUserRequest  $request  Solicitud validada de creación
     * @return array<string, mixed> Datos listos para persistencia
     */
    protected function buildCreatePayload(StaffUserRequest $request): array
    {
        $validatedData = $request->validated();
        /** @var mixed $rawPassword */
        $rawPassword = $validatedData['password'] ?? null;
        $validatedData['password'] = is_string($rawPassword)
            ? bcrypt($rawPassword)
            : '';

        return $validatedData;
    }

    /**
     * Construye el payload de actualización con manejo opcional de contraseña.
     *
     * @param  StaffUserRequest  $request  Solicitud validada de actualización
     * @return array<string, mixed> Datos listos para persistencia
     */
    protected function buildUpdatePayload(StaffUserRequest $request): array
    {
        $validatedData = $request->validated();
        /** @var mixed $rawPassword */
        $rawPassword = $validatedData['password'] ?? null;

        if (! is_string($rawPassword) || $rawPassword === '') {
            unset($validatedData['password']);
        } else {
            $validatedData['password'] = bcrypt($rawPassword);
            $validatedData['password_changed_at'] = now();
        }

        return $validatedData;
    }

    /**
     * Normaliza roles provenientes de request.
     *
     * @param  IlluminateRequest  $request  Solicitud HTTP
     * @return array<int, string|int> Roles filtrados para sincronización
     */
    protected function normalizeRoleInputs(IlluminateRequest $request): array
    {
        /** @var array<mixed> $incomingRoles */
        $incomingRoles = (array) $request->input('roles', []);

        return array_values(array_filter(
            $incomingRoles,
            static fn ($r): bool => is_string($r) || is_int($r)
        ));
    }
}

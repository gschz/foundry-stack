<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\AccountSecurity;

use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Interfaz para regenerar códigos de recuperación de 2FA.
 *
 * Genera un nuevo conjunto de códigos de recuperación y los devuelve
 * para que el usuario los almacene de forma segura.
 */
interface RegenerateTwoFactorRecoveryCodesInterface
{
    /**
     * Regenera los códigos de recuperación de 2FA para el usuario.
     *
     * @param  StaffUser  $user  Usuario de personal al que se regeneran los códigos.
     * @return list<string>
     */
    public function handle(StaffUser $user): array;
}

<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\AccountSecurity;

use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Interfaz para desactivar la autenticación de dos factores (2FA).
 *
 * Elimina la configuración de 2FA del usuario y limpia sus códigos de recuperación.
 */
interface DisableTwoFactorAuthInterface
{
    /**
     * Desactiva 2FA para el usuario dado.
     *
     * @param  StaffUser  $user  Usuario de personal al que se desactiva 2FA.
     *
     * @example
     *  $service->handle($user);
     */
    public function handle(StaffUser $user): void;
}

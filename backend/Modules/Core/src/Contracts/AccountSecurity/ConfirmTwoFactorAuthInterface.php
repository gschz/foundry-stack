<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\AccountSecurity;

use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Interfaz para confirmar el código de autenticación de dos factores (2FA).
 *
 * Valida el código de un usuario de personal y confirma la activación
 * del segundo factor cuando corresponda.
 */
interface ConfirmTwoFactorAuthInterface
{
    /**
     * Confirma el código 2FA del usuario.
     *
     * @param  StaffUser  $user  Usuario de personal que realiza la confirmación.
     * @param  string  $code  Código de verificación TOTP introducido por el usuario.
     * @return bool Verdadero si la confirmación es válida; falso en caso contrario.
     *
     * @example
     *  $ok = $service->handle($user, '123456');
     */
    public function handle(StaffUser $user, string $code): bool;
}

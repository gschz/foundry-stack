<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\AccountSecurity;

use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Interfaz para iniciar la configuración de autenticación de dos factores (2FA).
 *
 * Genera el secreto TOTP, la URI de aprovisionamiento y códigos de recuperación
 * iniciales para que el usuario configure su aplicación de autenticación.
 */
interface SetupTwoFactorAuthInterface
{
    /**
     * Inicia la configuración de 2FA para el usuario.
     *
     * @param  StaffUser  $user  Usuario de personal que activa 2FA.
     * @return array{secret:string,provisioning_uri:string,recovery_codes:list<string>}
     */
    public function handle(StaffUser $user): array;
}

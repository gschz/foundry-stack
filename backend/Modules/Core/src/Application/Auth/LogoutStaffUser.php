<?php

declare(strict_types=1);

namespace Modules\Core\Application\Auth;

use Modules\Core\Contracts\Auth\AuthenticatesUsersInterface;

/**
 * Caso de uso: cerrar sesión de usuario staff.
 */
final readonly class LogoutStaffUser
{
    public function __construct(
        private AuthenticatesUsersInterface $auth
    ) {
        //
    }

    /**
     * Cierra sesión; si hay suplantación activa, la detiene primero.
     */
    public function handle(): void
    {
        $this->auth->logout();
    }
}

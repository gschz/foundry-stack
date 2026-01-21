<?php

declare(strict_types=1);

namespace Modules\Core\Application\Auth;

use Modules\Core\Contracts\Auth\AuthenticatesUsersInterface;

/**
 * Caso de uso: iniciar sesión de usuario staff.
 */
final readonly class LoginStaffUser
{
    public function __construct(
        private AuthenticatesUsersInterface $auth
    ) {
        //
    }

    /**
     * Intenta iniciar sesión con credenciales.
     *
     * @param  array<string, mixed>  $credentials  ['email' => ..., 'password' => ...]
     * @param  bool  $remember  Recordar sesión
     * @return bool True si autenticó; False en caso contrario
     */
    public function handle(array $credentials, bool $remember = false): bool
    {
        return $this->auth->attempt($credentials, $remember);
    }
}

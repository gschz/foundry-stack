<?php

declare(strict_types=1);

namespace Modules\Core\Application\Auth;

use Modules\Core\Contracts\Auth\ImpersonatesUsersInterface;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Caso de uso: suplantación de usuario staff.
 */
final readonly class ImpersonateStaffUser
{
    public function __construct(
        private ImpersonatesUsersInterface $impersonator
    ) {
        //
    }

    /**
     * Inicia suplantación a partir del usuario destino.
     *
     * @param  StaffUser  $target  Usuario destino
     * @return bool True si la suplantación comenzó
     */
    public function handle(StaffUser $target): bool
    {
        $targetUser = $target;

        return $this->impersonator->impersonate($targetUser);
    }
}

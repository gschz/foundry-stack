<?php

declare(strict_types=1);

namespace Modules\Core\Application\AccountSecurity;

use Illuminate\Support\Facades\Log;
use Modules\Core\Contracts\AccountSecurity\DisableTwoFactorAuthInterface;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Caso de uso: desactivar la autenticación de dos factores del usuario.
 *
 * Limpia secreto, códigos de recuperación y estado de confirmación; registra
 * auditoría y actividad.
 */
final readonly class DisableTwoFactorAuth implements DisableTwoFactorAuthInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(StaffUser $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        Log::channel('domain_audit')->info('2FA deshabilitado', [
            'user_id' => $user->getAuthIdentifier(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event('two_factor_disabled')
            ->log('Deshabilitación de 2FA');
    }
}

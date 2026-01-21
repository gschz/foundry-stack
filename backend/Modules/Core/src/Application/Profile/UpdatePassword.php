<?php

declare(strict_types=1);

namespace Modules\Core\Application\Profile;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Caso de uso: actualizar la contraseña del usuario staff.
 *
 * Hash seguro, marca fecha de cambio y registra auditoría/actividad.
 */
final readonly class UpdatePassword
{
    /**
     * Actualiza la contraseña del usuario.
     *
     * @param  StaffUser  $user  Usuario staff.
     * @param  string  $newPassword  Nueva contraseña en texto plano.
     */
    public function handle(StaffUser $user, string $newPassword): void
    {
        $user->forceFill([
            'password' => Hash::make($newPassword),
            'password_changed_at' => now(),
        ])->save();

        Log::channel('domain_profile')->info('Password actualizado', [
            'user_id' => $user->getAuthIdentifier(),
            'email' => $user->email,
            'changed_at' => now()->toISOString(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event('password_updated')
            ->withProperties([
                'changed_at' => now()->toISOString(),
            ])
            ->log('Actualización de contraseña en el perfil de usuario');
    }
}

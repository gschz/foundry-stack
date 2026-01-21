<?php

declare(strict_types=1);

namespace Modules\Core\Application\Profile;

use Illuminate\Support\Facades\Log;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Caso de uso: actualizar datos de perfil del usuario staff.
 *
 * Normaliza atributos, invalida verificación de email si cambia y registra
 * auditoría/actividad con los cambios aplicados.
 */
final readonly class UpdateProfile
{
    /**
     * Actualiza el perfil del usuario con los datos proporcionados.
     *
     * @param  StaffUser  $user  Usuario staff.
     * @param  mixed  $data  Datos (array recomendado) con claves string.
     */
    public function handle(StaffUser $user, mixed $data): void
    {
        $attributes = [];
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $attributes[(string) $k] = $v;
            }
        }

        $user->fill($attributes);

        if ($user->isDirty('email')) {
            $user->setAttribute('email_verified_at', null);
        }

        $user->save();

        Log::channel('domain_profile')->info('Perfil actualizado', [
            'user_id' => $user->getAuthIdentifier(),
            'email' => $user->email,
            'changes' => $attributes,
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event('profile_updated')
            ->withProperties([
                'changes' => $attributes,
            ])
            ->log('Actualización perfil de usuario');
    }
}

<?php

declare(strict_types=1);

namespace Modules\Core\Application\NotificationPreferences;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Core\Contracts\NotificationPreferences\UpdateNotificationPreferencesInterface;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Caso de uso: actualizar preferencias de notificaciones del usuario.
 *
 * Normaliza y persiste las preferencias; registra auditoría/actividad.
 */
final readonly class UpdateNotificationPreferences implements UpdateNotificationPreferencesInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(StaffUser $user, mixed $preferences): void
    {
        $normalized = [];
        if (is_array($preferences)) {
            foreach ($preferences as $k => $v) {
                $normalized[(string) $k] = $v;
            }
        }

        $rawId = $user->getAuthIdentifier();
        $userId = is_string($rawId)
            ? $rawId : (
                is_int($rawId) ? (string) $rawId : null
            );

        if ($userId !== null) {
            Cache::put(
                'user.'.$userId.'.notification_preferences',
                $normalized,
                now()->addDays(30)
            );
        }

        Log::channel('domain_settings')->info('Preferencias de notificaciones actualizadas', [
            'user_id' => $user->getAuthIdentifier(),
            'preferences' => $normalized,
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event('notification_preferences_updated')
            ->withProperties([
                'preferences' => $normalized,
            ])
            ->log('Actualización de preferencias de notificaciones');
    }
}

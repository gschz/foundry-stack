<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\NotificationPreferences;

use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Interfaz para actualizar las preferencias de notificación del usuario.
 *
 * Permite aplicar cambios de canales y opciones (email, interno, etc.)
 * de forma desacoplada de la infraestructura concreta.
 */
interface UpdateNotificationPreferencesInterface
{
    /**
     * Actualiza las preferencias de notificación del usuario.
     *
     * @param  StaffUser  $user  Usuario de personal.
     * @param  mixed  $preferences  Preferencias (ej. array<string, mixed> con claves 'email', 'internal', etc.).
     *
     * @example
     *  $service->handle($user, ['email' => true, 'internal' => ['alerts' => ['security' => true]]]);
     */
    public function handle(StaffUser $user, mixed $preferences): void;
}

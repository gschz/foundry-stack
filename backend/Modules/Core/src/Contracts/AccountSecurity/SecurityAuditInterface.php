<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\AccountSecurity;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/**
 * Interfaz para auditoría y seguridad de sesión.
 *
 * Define operaciones transversales de seguridad: preparación de sesión
 * tras login, notificación de inicios sospechosos y cierre de sesión.
 */
interface SecurityAuditInterface
{
    /**
     * Prepara la sesión después de una autenticación exitosa.
     */
    public function prepareAuthenticatedSession(Request $request): void;

    /**
     * Maneja la notificación de inicio de sesión para inicios de sesión sospechosos.
     */
    public function handleSuspiciousLoginNotification(Authenticatable $user, Request $request): void;

    /**
     * Cierra la sesión del usuario para un guard específico.
     */
    public function logout(Request $request, string $guard): void;
}

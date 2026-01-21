<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\AccountSecurity;

/**
 * Interfaz para gestión de intentos de inicio de sesión y rate limiting.
 *
 * Estandariza verificación de umbrales, limpieza de contadores y bloqueo
 * por IP/identificador para endurecer el flujo de autenticación.
 */
interface LoginAttemptInterface
{
    /**
     * Verifica si el usuario o IP ha excedido el límite de intentos fallidos.
     */
    public function hasTooManyAttempts(string $identifier, string $ip): bool;

    /**
     * Incrementa el contador de intentos fallidos.
     */
    public function incrementAttempts(string $identifier, string $ip): void;

    /**
     * Limpia los intentos fallidos para un usuario/IP específico.
     */
    public function clearAttempts(string $identifier, string $ip): void;

    /**
     * Obtiene la cantidad de intentos fallidos.
     */
    public function getAttempts(string $identifier, string $ip): int;

    /**
     * Obtiene los minutos restantes hasta que se quite el bloqueo.
     */
    public function getRemainingMinutes(string $identifier, string $ip): int;

    /**
     * Verifica si una IP está bloqueada por sospecha de ataque.
     */
    public function isIpBlocked(string $ip): bool;
}

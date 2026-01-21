<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

/**
 * Interfaz para registrar eventos de auditoría del dominio.
 *
 * Estandariza la escritura de eventos en el canal de auditoría y permite
 * desacoplar listeners/servicios de la implementación concreta de logging.
 */
interface AuditTrailInterface
{
    /**
     * Registra un evento de auditoría.
     *
     * @param  string  $event  Nombre canónico del evento (por ejemplo: 'sensitive_action').
     * @param  array<string, mixed>  $context  Contexto adicional serializable.
     */
    public function record(string $event, array $context = []): void;
}

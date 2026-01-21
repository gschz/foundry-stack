<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Illuminate\Support\Facades\Log;
use Modules\Core\Contracts\AuditTrailInterface;

/**
 * Servicio de auditoría de dominio (implementación Laravel).
 *
 * Escribe eventos en el canal 'domain_audit' con formato JSON.
 * Normaliza claves del contexto para garantizar compatibilidad.
 */
final class AuditTrailService implements AuditTrailInterface
{
    /**
     * {@inheritDoc}
     *
     * Nota: Normaliza claves de contexto y agrega timestamp ISO si falta.
     */
    public function record(string $event, array $context = []): void
    {
        $normalized = [];
        foreach ($context as $k => $v) {
            $normalized[(string) $k] = $v;
        }

        if (! isset($normalized['timestamp'])) {
            $normalized['timestamp'] = now()->toIso8601String();
        }

        Log::channel('domain_audit')->info($event, $normalized);
    }
}

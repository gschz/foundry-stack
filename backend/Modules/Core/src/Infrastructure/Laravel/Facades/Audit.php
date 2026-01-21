<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\Contracts\AuditTrailInterface;

/**
 * Facade para registrar eventos en el canal de auditoría del dominio.
 *
 * @method static void record(string $event, array<string, mixed> $context = [])
 *
 * @see AuditTrailInterface
 */
final class Audit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AuditTrailInterface::class;
    }
}

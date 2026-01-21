<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Facades;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Modules\Core\Contracts\AccountSecurity\SecurityAuditInterface;

/**
 * Facade para operaciones de auditoría y seguridad de sesiones.
 *
 * @method static void prepareAuthenticatedSession(Request $request)
 * @method static void handleSuspiciousLoginNotification(Authenticatable $user, Request $request)
 * @method static void logout(Request $request, string $guard)
 *
 * @see SecurityAuditInterface
 */
final class SecurityAudit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SecurityAuditInterface::class;
    }
}

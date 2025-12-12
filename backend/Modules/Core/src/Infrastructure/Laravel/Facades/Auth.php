<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Facades;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Facade;
use Modules\Core\Infrastructure\Laravel\Services\AuthService;

/**
 * Facade para el servicio de autenticación del Core.
 *
 * @method static bool attempt(array<string, mixed> $credentials, bool $remember = false)
 * @method static void logout()
 * @method static Authenticatable|null user()
 * @method static bool check()
 * @method static int|string|null id()
 * @method static bool impersonate(Authenticatable $user)
 * @method static bool stopImpersonating()
 * @method static bool isImpersonating()
 *
 * @see AuthService
 */
final class Auth extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AuthService::class;
    }
}

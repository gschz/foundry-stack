<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Contracts\Auth\AuthenticatesUsers;
use Modules\Core\Contracts\Auth\ImpersonatesUsers;

/**
 * Servicio de autenticación.
 * Implementa las interfaces de autenticación y suplantación del Core.
 */
final class AuthService implements AuthenticatesUsers, ImpersonatesUsers
{
    private const string IMPERSONATION_SESSION_KEY = 'impersonated_by';

    /**
     * {@inheritDoc}
     */
    public function attempt(array $credentials, bool $remember = false): bool
    {
        return Auth::guard('staff')->attempt($credentials, $remember);
    }

    /**
     * {@inheritDoc}
     */
    public function logout(): void
    {
        if ($this->isImpersonating()) {
            $this->stopImpersonating();

            return;
        }

        Auth::guard('staff')->logout();
    }

    /**
     * {@inheritDoc}
     */
    public function user(): ?Authenticatable
    {
        /** @var Authenticatable|null */
        return Auth::guard('staff')->user();
    }

    /**
     * {@inheritDoc}
     */
    public function check(): bool
    {
        return Auth::guard('staff')->check();
    }

    /**
     * {@inheritDoc}
     */
    public function id()
    {
        return Auth::guard('staff')->id();
    }

    /**
     * {@inheritDoc}
     */
    public function impersonate(Authenticatable $user): bool
    {
        $originalUser = $this->user();

        if (! $originalUser instanceof Authenticatable) {
            return false;
        }

        // Almacenar el ID del usuario original en la sesión
        session()->put(
            self::IMPERSONATION_SESSION_KEY,
            $originalUser->getAuthIdentifier()
        );

        // Iniciar sesión como el nuevo usuario
        Auth::guard('staff')->login($user);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function stopImpersonating(): bool
    {
        if (! $this->isImpersonating()) {
            return false;
        }

        $originalUserId = session()->pull(self::IMPERSONATION_SESSION_KEY);

        /** @var class-string<\Illuminate\Database\Eloquent\Model> $userModelClass */
        $userModelClass = config('auth.providers.staff_users.model');

        /** @var Authenticatable|null $originalUser */
        $originalUser = $userModelClass::query()->find($originalUserId);

        if ($originalUser) {
            Auth::guard('staff')->login($originalUser);

            return true;
        }

        // Si no se encuentra el usuario original, hacer logout completo por seguridad
        Auth::guard('staff')->logout();

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isImpersonating(): bool
    {
        return session()->has(self::IMPERSONATION_SESSION_KEY);
    }
}

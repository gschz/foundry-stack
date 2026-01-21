<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Traits;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Core\Contracts\Auth\ImpersonatesUsersInterface;

/**
 * Trait para ofrecer utilidades de suplantación (impersonation) en modelos.
 *
 * Delegado al servicio de autenticación del Core.
 *
 * @mixin Authenticatable
 */
trait CanBeImpersonated
{
    /**
     * Inicia suplantación iniciando sesión como la instancia actual.
     *
     * @return bool True si se inició la suplantación; False en caso contrario
     */
    public function impersonate(): bool
    {
        /** @var ImpersonatesUsersInterface $svc */
        $svc = app(ImpersonatesUsersInterface::class);

        return $svc->impersonate($this);
    }

    /**
     * Finaliza la suplantación (restaura usuario original o cierra sesión).
     *
     * @return bool True si se restauró; False si se cerró sesión
     */
    public function stopImpersonating(): bool
    {
        /** @var ImpersonatesUsersInterface $svc */
        $svc = app(ImpersonatesUsersInterface::class);

        return $svc->stopImpersonating();
    }

    /**
     * Indica si existe suplantación activa en la sesión.
     */
    public function isImpersonating(): bool
    {
        /** @var ImpersonatesUsersInterface $svc */
        $svc = app(ImpersonatesUsersInterface::class);

        return $svc->isImpersonating();
    }
}

<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Interfaz para la suplantación de identidad (impersonation).
 */
interface ImpersonatesUsers
{
    /**
     * Inicia la suplantación de un usuario.
     *
     * @param  Authenticatable  $user  El usuario a suplantar.
     * @return bool Verdadero si la suplantación fue exitosa.
     */
    public function impersonate(Authenticatable $user): bool;

    /**
     * Detiene la suplantación actual y restaura el usuario original.
     *
     * @return bool Verdadero si se detuvo la suplantación exitosamente.
     */
    public function stopImpersonating(): bool;

    /**
     * Verifica si se está suplantando a un usuario.
     */
    public function isImpersonating(): bool;
}

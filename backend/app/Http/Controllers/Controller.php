<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Controlador base de la aplicación.
 *
 * Proporciona funcionalidades comunes como la autorización de solicitudes,
 * el despacho de trabajos y la validación de datos a todos los controladores
 * que heredan de esta clase.
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    /**
     * Obtiene el usuario autenticado del guard 'staff' o aborta con 403.
     */
    protected function requireStaffUser(Request $request): StaffUser
    {
        /** @var StaffUser|null $user */
        $user = $request->user('staff');

        abort_unless($user instanceof StaffUser, 403, 'Usuario no autenticado');

        return $user;
    }
}

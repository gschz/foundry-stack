<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Rutas Web del Módulo de Administración
|--------------------------------------------------------------------------
|
| Aquí se definen las rutas para el panel de administración y la gestión
| de usuarios del personal (Staff). Todas las rutas están protegidas por
| el guard 'staff' y permisos específicos.
|
*/

use Illuminate\Support\Facades\Route;
use Modules\Admin\App\Http\Controllers\AdminDashboardController;

Route::middleware([
    'auth:staff',
    'verified',
    'throttle:60,1',
    'permission:access-admin,staff',
])->prefix('internal/staff/admin')->name('internal.staff.admin.')->group(
    function (): void {

        // La ruta principal del panel de administración.
        // GET /internal/staff/admin
        Route::get(
            '/',
            [AdminDashboardController::class, 'index']
        )->name('index');

        // Rutas para la gestión de usuarios (CRUD de vistas).
        require_once sprintf('%s/users.php', __DIR__);
    }
);

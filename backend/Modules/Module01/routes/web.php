<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Rutas Web del Módulo 01
|--------------------------------------------------------------------------
|
| Todas las rutas están prefijadas con '/internal/staff/module-01' y protegidas
| por el guard 'staff' y el permiso base del módulo.
|
*/

use Illuminate\Support\Facades\Route;
use Modules\Module01\App\Http\Controllers\Module01DashboardController;

/**
 * Grupo principal de rutas para el Módulo 01.
 * Prefijo de URL: '/internal/staff/module-01'
 * Prefijo de Nombre de Ruta: 'internal.staff.module01.'
 * Middleware base: 'auth', 'verified'
 */
Route::middleware([
    'auth:staff',
    'verified',
    'throttle:60,1',
    'permission:access-module-01,staff',
])->prefix('internal/staff/module-01')->name('internal.staff.module01.')->group(
    function (): void {
        /**
         * Muestra el panel principal del Módulo 01.
         * URL: /internal/staff/module-01
         * Nombre de Ruta: internal.staff.module01.index
         * Controlador: Module01DashboardController@index
         * Permiso Requerido: access-module-01
         */
        Route::get(
            '/',
            [Module01DashboardController::class, 'index']
        )->name('index');
    }
);

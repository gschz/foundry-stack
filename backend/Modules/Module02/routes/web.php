<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Rutas Web del Módulo 02
|--------------------------------------------------------------------------
|
| Todas las rutas están prefijadas con '/internal/staff/module-02' y protegidas
| por el guard 'staff' y el permiso base del módulo.
|
 */

use Illuminate\Support\Facades\Route;
use Modules\Module02\App\Http\Controllers\Module02DashboardController;

/**
 * Grupo principal de rutas para el Módulo 02.
 * Prefijo de URL: '/internal/staff/module-02'
 * Prefijo de Nombre de Ruta: 'internal.staff.module02.'
 * Middleware base: 'auth', 'verified'
 */
Route::middleware([
    'auth:staff',
    'verified',
    'throttle:60,1',
    'permission:access-module-02,staff',
])->prefix('internal/staff/module-02')->name('internal.staff.module02.')->group(
    function (): void {
        /**
         * Muestra el panel principal del Módulo 02.
         * URL: /internal/staff/module-02
         * Nombre de Ruta: internal.staff.module02.index
         * Controlador: Module02DashboardController@index
         * Permiso Requerido: access-module-02
         */
        Route::get(
            '/',
            [Module02DashboardController::class, 'index']
        )->name('index');
    }
);

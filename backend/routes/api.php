<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de la API Pública
|--------------------------------------------------------------------------
|
| Este archivo define las rutas para la API pública de la aplicación.
| Todas las rutas aquí definidas son agrupadas bajo el middleware 'api'
| y tienen un prefijo '/api' automáticamente aplicado por Laravel.
|
*/

// Grupo de rutas para la API con limitador de peticiones global.
Route::middleware(['throttle:api', 'api'])->group(function (): void {
    //
});

/**
 * Endpoint de Health Check.
 * Utilizado por servicios de monitoreo para verificar que la aplicación está en línea y funcionando.
 *
 * GET /api/health
 */
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'timestamp' => now()->toIso8601String(),
]))->name('api.health');

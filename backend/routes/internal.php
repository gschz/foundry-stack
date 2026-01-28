<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginDeviceController;
use App\Http\Controllers\Internal\MainDashboardController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Internas
|--------------------------------------------------------------------------
|
| Grupo de rutas para el panel de administración. Todas están prefijadas
| con '/internal'.
|
*/

// Grupo de rutas de autenticación internas: prefijo de URI 'internal' pero SIN prefijo de nombre
Route::prefix('internal')->group(function (): void {
    require_once sprintf('%s/auth.php', __DIR__);
});

// Grupo de rutas internas con prefijo de nombre 'internal.'
Route::prefix('internal')->name('internal.')->group(function (): void {
    /**
     * Grupo de rutas que requieren que el usuario del personal esté
     * autenticado y haya verificado su correo electrónico.
     */
    Route::middleware([
        'auth:staff',
        'verified',
        'throttle:60,1',
    ])->prefix('staff')->name('staff.')->group(function (): void {
        /**
         * Panel de control principal.
         * GET /internal/staff/dashboard
         */
        Route::get(
            '/dashboard',
            [MainDashboardController::class, 'index']
        )->name('dashboard');

        /**
         * Marca un dispositivo como confiable a través de un enlace firmado.
         * GET /internal/staff/trust-device/{id}
         */
        Route::get(
            '/trust-device/{loginInfo}',
            [LoginDeviceController::class, 'trust']
        )->middleware('signed')->name('login.trust-device');
    });

    Route::middleware([
        'auth:staff',
        'verified',
        'throttle:60,1',
    ])->group(function (): void {
        Route::redirect('/dashboard', '/internal/staff/dashboard', 301);

        Route::get('/admin/{path?}', static function (?string $path = null): RedirectResponse {
            $target = is_string($path) && $path !== ''
                ? sprintf('/internal/staff/admin/%s', $path)
                : '/internal/staff/admin';

            return redirect($target, 301);
        })->where('path', '.*');

        Route::redirect('/module-01', '/internal/staff/module-01', 301);
        Route::redirect('/module-02', '/internal/staff/module-02', 301);
    });
});

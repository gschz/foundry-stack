<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tighten\Ziggy\Ziggy;

/**
 * Proveedor de servicios para la configuración de rutas.
 */
final class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define los enlaces de modelos de ruta, filtros de patrones y otra configuración de rutas.
     */
    public function boot(): void
    {
        Route::pattern('id', '[0-9]+');
        Route::pattern('slug', '[a-z0-9-]+');

        $this->configureRateLimiting();
        $this->configureZiggyRouteGroups();

        $this->routes(function (): void {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });

        $this->registerZiggyRoutes();
    }

    /**
     * Configura los limitadores de velocidad para la aplicación.
     */
    private function configureRateLimiting(): void
    {
        // Límite general para la API: 60 solicitudes por minuto.
        RateLimiter::for(
            'api',
            fn (Request $request) => Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
        );

        // Límite para intentos de autenticación: 5 por minuto por IP.
        RateLimiter::for(
            'auth',
            fn (Request $request) => Limit::perMinute(5)->by($request->ip())
        );

        // Límite para operaciones con Sanctum (ej. emisión de tokens).
        RateLimiter::for(
            'sanctum',
            fn (Request $request) => Limit::perMinute(10)->by($request->ip())
        );
    }

    /**
     * Define los grupos de rutas para Ziggy
     * Esto permite que podamos filtrar qué rutas se envían al frontend
     * basándonos en los grupos definidos en Ziggy.
     */
    private function configureZiggyRouteGroups(): void
    {
        // Rutas públicas (accesibles para visitantes no autenticados)
        config(['ziggy.groups.public' => [
            'welcome',
            'register.redirect',
            'login',
            'login.store',
            'password.*',
            'sanctum.csrf-cookie',
        ]]);

        // Rutas para el panel interno de personal
        config(['ziggy.groups.staff' => [
            'internal.*',
            'logout',
            'verification.*',
            'password.confirm',
            'password.confirm.store',
            'sanctum.csrf-cookie',
            'storage.local',
        ]]);
    }

    /**
     * Registra la ruta para que Ziggy genere las rutas del lado del cliente.
     */
    private function registerZiggyRoutes(): void
    {
        Route::get('/api/routes', function (Request $request) {
            $groupsRaw = $request->input('groups');
            $groupsInput = is_string($groupsRaw) ? $groupsRaw : '';

            /** @var array<int, string> $requestedGroups */
            $requestedGroups = $groupsInput === ''
                ? []
                : array_filter(
                    array_map(
                        mb_trim(...),
                        explode(',', $groupsInput)
                    ),
                    static fn (string $g): bool => $g !== ''
                );

            // Asegura que el grupo 'public' siempre esté disponible
            $groupsToFilter = array_unique(
                array_merge(
                    ['public'],
                    $requestedGroups
                )
            );

            return app(Ziggy::class)
                ->filter($groupsToFilter)
                ->toArray();
        })->middleware('web');
    }
}

<?php

declare(strict_types=1);

namespace App\Providers;

use App\Interfaces\ApiResponseFormatterInterface;
use App\Services\ApiResponseService;
use App\Services\JsonbQueryService;
use App\Services\RouteFilterService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Proveedor de servicios principal de la aplicación
 */
final class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra los servicios de la aplicación en el contenedor de dependencias.
     */
    public function register(): void
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;

        // Establece una ruta personalizada para la base de datos.
        $app->useDatabasePath(base_path('../database'));

        // Registra Telescope solo en entornos de no producción para depuración.
        if (! $this->app->environment('production')) {
            $this->app->register(
                \Laravel\Telescope\TelescopeServiceProvider::class
            );
            $this->app->register(TelescopeServiceProvider::class);
        }

        // Registrar las interfaces del sistema con sus implementaciones concretas.
        $this->app->singleton(
            ApiResponseFormatterInterface::class,
            ApiResponseService::class
        );
        $this->app->singleton(JsonbQueryService::class);
        $this->app->singleton(RouteFilterService::class);
    }

    /**
     * Arranca los servicios de la aplicación después de que se hayan registrado.
     */
    public function boot(): void
    {
        // Define una regla global 'before' para la autorización.
        Gate::before(function ($user, $ability) {
            if (
                $user instanceof StaffUser
                && ($user->hasRole('ADMIN') || $user->hasRole('DEV'))
            ) {
                return true;
            }
        });
    }
}

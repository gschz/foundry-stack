<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

/**
 * Proveedor de servicios para Laravel Telescope.
 */
final class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Registra los servicios de la aplicación para Telescope.
     */
    public function register(): void
    {
        Telescope::night();

        // Oculta detalles sensibles de las peticiones para proteger la privacidad.
        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        // Define qué entradas deben ser registradas por Telescope.
        // En entornos locales, se registra todo. En otros entornos, solo se registran
        // excepciones, peticiones fallidas, trabajos fallidos, tareas programadas y etiquetas monitoreadas.
        Telescope::filter(
            function (IncomingEntry $entry) use ($isLocal): bool {
                if ($isLocal) {
                    return true;
                }

                if ($entry->isReportableException()) {
                    return true;
                }

                if ($entry->isFailedRequest()) {
                    return true;
                }

                if ($entry->isFailedJob()) {
                    return true;
                }

                if ($entry->isScheduledTask()) {
                    return true;
                }

                return $entry->hasMonitoredTag();
            }
        );
    }

    /**
     * Registra la puerta de acceso (gate) para Telescope.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user): bool {
            if (! is_object($user)) {
                return false;
            }

            // Por defecto, solo los usuarios con roles de 'ADMIN' o 'DEV' pueden acceder a Telescope.
            if (method_exists($user, 'hasRole')) {
                if ($user->hasRole('ADMIN')) {
                    return true;
                }

                return (bool) $user->hasRole('DEV');
            }

            return false;
        });
    }

    /**
     * Evita que detalles sensibles de las peticiones sean registrados por Telescope.
     */
    private function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        // Oculta el token CSRF de los parámetros de la petición.
        Telescope::hideRequestParameters(['_token']);

        // Oculta cabeceras que puedan contener información de sesión o tokens.
        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }
}

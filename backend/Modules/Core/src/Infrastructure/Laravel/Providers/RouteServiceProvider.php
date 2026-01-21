<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Provider para el registro de rutas del módulo Core.
 */
final class RouteServiceProvider extends ServiceProvider
{
    private string $moduleNamespace = 'Modules\\Core\\Infrastructure\\Laravel\\Http\\Controllers';

    public function register(): void
    {
        parent::register();
    }

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapWebRoutes();
        $this->mapApiRoutes();
    }

    private function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Core', '/routes/web.php'));
    }

    private function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Core', '/routes/api.php'));
    }
}

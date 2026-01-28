<?php

declare(strict_types=1);

namespace Modules\Module02\App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\StatsServiceInterface;
use Modules\Module02\App\Http\Controllers\AbstractModule02Controller;
use Modules\Module02\App\Http\Controllers\Module02DashboardController;
use Modules\Module02\App\Services\Module02StatsService;

/**
 * Provider principal del módulo Module02.
 * Registra y arranca los servicios necesarios del módulo.
 */
final class Module02ServiceProvider extends ServiceProvider
{
    private string $moduleName = 'Module02';

    private string $moduleNameLower = 'module02';

    /**
     * Registra servicios, bindings y comandos del módulo.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->loadMigrationsFrom(
            module_path($this->moduleName, 'database/migrations')
        );

        // Contextual binding para evitar colisiones globales del contrato StatsServiceInterface
        $this->app->when(AbstractModule02Controller::class)
            ->needs(StatsServiceInterface::class)
            ->give(Module02StatsService::class);
        $this->app->when(Module02DashboardController::class)
            ->needs(StatsServiceInterface::class)
            ->give(Module02StatsService::class);
    }

    /**
     * Arranca servicios del módulo.
     */
    public function boot(): void
    {
        $this->registerConfig();
    }

    /**
     * Registra la configuración del módulo.
     */
    private function registerConfig(): void
    {
        $this->publishes([
            module_path(
                $this->moduleName,
                'config/config.php'
            ) => config_path($this->moduleNameLower.'.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Module01\App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\StatsServiceInterface;
use Modules\Module01\App\Http\Controllers\AbstractModule01Controller;
use Modules\Module01\App\Http\Controllers\Module01DashboardController;
use Modules\Module01\App\Services\Module01StatsService;

/**
 * Provider principal del módulo Module01.
 * Registra y arranca los servicios necesarios del módulo.
 */
final class Module01ServiceProvider extends ServiceProvider
{
    private string $moduleName = 'Module01';

    private string $moduleNameLower = 'module01';

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
        $this->app->when(AbstractModule01Controller::class)
            ->needs(StatsServiceInterface::class)
            ->give(Module01StatsService::class);
        $this->app->when(Module01DashboardController::class)
            ->needs(StatsServiceInterface::class)
            ->give(Module01StatsService::class);
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

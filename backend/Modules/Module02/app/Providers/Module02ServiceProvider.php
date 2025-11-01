<?php

declare(strict_types=1);

namespace Modules\Module02\App\Providers;

use App\Interfaces\StatsServiceInterface;
use Illuminate\Support\ServiceProvider;
use Modules\Module02\App\Http\Controllers\Module02BaseController;
use Modules\Module02\App\Http\Controllers\Module02PanelController;
use Modules\Module02\App\Services\Module02StatsService;

final class Module02ServiceProvider extends ServiceProvider
{
    private string $moduleName = 'Module02';

    private string $moduleNameLower = 'module02';

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Contextual binding para evitar colisiones globales del contrato StatsServiceInterface
        $this->app->when(Module02BaseController::class)
            ->needs(StatsServiceInterface::class)
            ->give(Module02StatsService::class);
        $this->app->when(Module02PanelController::class)
            ->needs(StatsServiceInterface::class)
            ->give(Module02StatsService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerConfig();
    }

    /**
     * Register configs.
     */
    private function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );
    }
}

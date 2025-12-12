<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\Auth\AuthenticatesUsers;
use Modules\Core\Contracts\Auth\ImpersonatesUsers;
use Modules\Core\Contracts\ModuleRegistryInterface;
use Modules\Core\Contracts\NavigationBuilderInterface;
use Modules\Core\Contracts\PermissionVerifierInterface;
use Modules\Core\Contracts\ViewComposerInterface;
use Modules\Core\Infrastructure\Laravel\Services\AuthService;
use Modules\Core\Infrastructure\Laravel\Services\ModuleRegistryService;
use Modules\Core\Infrastructure\Laravel\Services\NavigationBuilderService;
use Modules\Core\Infrastructure\Laravel\Services\PermissionService;
use Modules\Core\Infrastructure\Laravel\Services\ViewComposerService;

/**
 * Provider principal del módulo Core.
 * Registra y arranca todos los servicios específicos del módulo.
 */
final class CoreServiceProvider extends ServiceProvider
{
    private string $moduleName = 'Core';

    private string $moduleNameLower = 'core';

    /**
     * Registra servicios, bindings y comandos del módulo.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(EventServiceProvider::class);

        $this->loadMigrationsFrom(
            module_path($this->moduleName, 'database/migrations')
        );

        // Registrar servicios principales
        $this->app->singleton(
            ModuleRegistryInterface::class,
            ModuleRegistryService::class
        );
        $this->app->singleton(
            NavigationBuilderInterface::class,
            NavigationBuilderService::class
        );
        $this->app->singleton(
            ViewComposerInterface::class,
            ViewComposerService::class
        );

        // Registrar servicios de Auth y Permisos
        $this->app->singleton(
            AuthService::class,
            AuthService::class
        );
        $this->app->singleton(
            PermissionService::class,
            PermissionService::class
        );

        // Bindings de interfaces de Auth
        $this->app->bind(
            AuthenticatesUsers::class,
            AuthService::class
        );
        $this->app->bind(
            ImpersonatesUsers::class,
            AuthService::class
        );
        $this->app->bind(
            PermissionVerifierInterface::class,
            PermissionService::class
        );
    }

    /**
     * Realiza tareas de arranque después de que todos los servicios están registrados.
     */
    public function boot(): void
    {
        $this->registerConfig();

        // Registrar alias de Facades
        $loader = AliasLoader::getInstance();
        $loader->alias(
            'Mod',
            \Modules\Core\Infrastructure\Laravel\Facades\Mod::class
        );
        $loader->alias(
            'Nav',
            \Modules\Core\Infrastructure\Laravel\Facades\Nav::class
        );
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

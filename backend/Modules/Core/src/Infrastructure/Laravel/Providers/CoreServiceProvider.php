<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Application\AccountSecurity\ConfirmTwoFactorAuth;
use Modules\Core\Application\AccountSecurity\DisableTwoFactorAuth;
use Modules\Core\Application\AccountSecurity\RegenerateTwoFactorRecoveryCodes;
use Modules\Core\Application\AccountSecurity\RevokeOtherSessions;
use Modules\Core\Application\AccountSecurity\SetupTwoFactorAuth;
use Modules\Core\Application\NotificationPreferences\UpdateNotificationPreferences;
use Modules\Core\Contracts\AccountSecurity\ConfirmTwoFactorAuthInterface;
use Modules\Core\Contracts\AccountSecurity\DisableTwoFactorAuthInterface;
use Modules\Core\Contracts\AccountSecurity\LoginAttemptInterface;
use Modules\Core\Contracts\AccountSecurity\RegenerateTwoFactorRecoveryCodesInterface;
use Modules\Core\Contracts\AccountSecurity\RevokeOtherSessionsInterface;
use Modules\Core\Contracts\AccountSecurity\SecurityAuditInterface;
use Modules\Core\Contracts\AccountSecurity\SetupTwoFactorAuthInterface;
use Modules\Core\Contracts\AddonRegistryInterface;
use Modules\Core\Contracts\AuditTrailInterface;
use Modules\Core\Contracts\Auth\AuthenticatesUsersInterface;
use Modules\Core\Contracts\Auth\ImpersonatesUsersInterface;
use Modules\Core\Contracts\MenuBuilderInterface;
use Modules\Core\Contracts\ModuleOrchestratorInterface;
use Modules\Core\Contracts\NotificationPreferences\UpdateNotificationPreferencesInterface;
use Modules\Core\Contracts\PermissionVerifierInterface;
use Modules\Core\Contracts\ViewComposerInterface;
use Modules\Core\Infrastructure\Laravel\Console\Commands\SyncGuardPermissionsCommand;
use Modules\Core\Infrastructure\Laravel\Services\AddonRegistryService;
use Modules\Core\Infrastructure\Laravel\Services\AuditTrailService;
use Modules\Core\Infrastructure\Laravel\Services\AuthService;
use Modules\Core\Infrastructure\Laravel\Services\LoginAttemptService;
use Modules\Core\Infrastructure\Laravel\Services\MenuBuilderService;
use Modules\Core\Infrastructure\Laravel\Services\ModuleOrchestratorService;
use Modules\Core\Infrastructure\Laravel\Services\PermissionService;
use Modules\Core\Infrastructure\Laravel\Services\SecurityAuditService;
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

        // Map interface => concrete singletons
        $singletons = [
            AddonRegistryInterface::class => AddonRegistryService::class,
            MenuBuilderInterface::class => MenuBuilderService::class,
            ModuleOrchestratorInterface::class => ModuleOrchestratorService::class,
            ViewComposerInterface::class => ViewComposerService::class,
            AuditTrailInterface::class => AuditTrailService::class,
            SecurityAuditInterface::class => SecurityAuditService::class,
            LoginAttemptInterface::class => LoginAttemptService::class,
            AuthService::class => AuthService::class,
            PermissionService::class => PermissionService::class,
        ];

        foreach ($singletons as $abstract => $concrete) {
            $this->app->singleton($abstract, $concrete);
        }

        // Map interface => concrete binds
        $binds = [
            AuthenticatesUsersInterface::class => AuthService::class,
            ImpersonatesUsersInterface::class => AuthService::class,
            PermissionVerifierInterface::class => PermissionService::class,
            SetupTwoFactorAuthInterface::class => SetupTwoFactorAuth::class,
            ConfirmTwoFactorAuthInterface::class => ConfirmTwoFactorAuth::class,
            DisableTwoFactorAuthInterface::class => DisableTwoFactorAuth::class,
            RegenerateTwoFactorRecoveryCodesInterface::class => RegenerateTwoFactorRecoveryCodes::class,
            RevokeOtherSessionsInterface::class => RevokeOtherSessions::class,
            UpdateNotificationPreferencesInterface::class => UpdateNotificationPreferences::class,
        ];

        foreach ($binds as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }

    /**
     * Realiza tareas de arranque después de que todos los servicios están registrados.
     */
    public function boot(): void
    {
        $this->registerConfig();

        $this->commands([
            SyncGuardPermissionsCommand::class,
        ]);

        $facades = [
            'Addon' => \Modules\Core\Infrastructure\Laravel\Facades\Addon::class,
            'Menu' => \Modules\Core\Infrastructure\Laravel\Facades\Menu::class,
            'Audit' => \Modules\Core\Infrastructure\Laravel\Facades\Audit::class,
            'SecurityAudit' => \Modules\Core\Infrastructure\Laravel\Facades\SecurityAudit::class,
            'ViewComposer' => \Modules\Core\Infrastructure\Laravel\Facades\ViewComposer::class,
        ];

        // Registrar alias de Facades
        $loader = AliasLoader::getInstance();

        foreach ($facades as $alias => $facade) {
            $loader->alias(
                $alias,
                $facade
            );
        }
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

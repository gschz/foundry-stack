<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Core\Infrastructure\Laravel\Events\MenuPermissionDenied;
use Modules\Core\Infrastructure\Laravel\Listeners\CacheUserPermissionsListener;
use Modules\Core\Infrastructure\Laravel\Listeners\LogSensitiveActionListener;
use Modules\Core\Infrastructure\Laravel\Listeners\MenuPermissionDenialListener;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;

/**
 * Provider de eventos del módulo Core.
 */
final class EventServiceProvider extends ServiceProvider
{
    /**
     * Los mapeos de escuchas de eventos para la aplicación.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Eventos de Spatie Permission
        RoleAttached::class => [
            CacheUserPermissionsListener::class,
            LogSensitiveActionListener::class,
        ],
        RoleDetached::class => [
            CacheUserPermissionsListener::class,
            LogSensitiveActionListener::class,
        ],
        PermissionAttached::class => [
            CacheUserPermissionsListener::class,
            LogSensitiveActionListener::class,
        ],
        PermissionDetached::class => [
            CacheUserPermissionsListener::class,
            LogSensitiveActionListener::class,
        ],
        MenuPermissionDenied::class => [
            MenuPermissionDenialListener::class,
        ],
    ];

    /**
     * Registra cualquier evento para su aplicación.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determina si los eventos y escuchas deben descubrirse automáticamente.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

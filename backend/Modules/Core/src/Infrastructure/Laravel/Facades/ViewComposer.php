<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\Infrastructure\Laravel\Services\ViewComposerService;

/**
 * Facade para componer y preparar datos para vistas de Inertia.
 * Simplifica la preparación de props comunes y contextos de módulo y dashboard.
 *
 * @method static array<string, mixed> prepareModuleViewData(string $moduleSlug, array<int, array<string, mixed>>|array<string, mixed> $panelItemsConfig, callable $permissionChecker, string $functionalName, array<int, mixed>|array<string, mixed>|null $stats = null, array<string, mixed> $data = [])
 * @method static \Inertia\Response renderModuleView(string $view, string $moduleViewPath, array<string, mixed> $data = [])
 * @method static array<string, mixed> getFlashMessages(\Illuminate\Http\Request $request)
 * @method static array<string, mixed> composeModuleViewContext(string $moduleSlug, array<int, array<string, mixed>> $panelItemsConfig, array<int, array<string, mixed>> $contextualNavItemsConfig, callable $permissionChecker, $user, ?string $functionalName = null, array<string, mixed> $data = [], array<int, mixed>|array<string, mixed>|null $stats = null, ?string $routeSuffix = null, array<string, mixed> $routeParams = [])
 * @method static array<string, mixed> composeDashboardViewContext($user, array<int, \Nwidart\Modules\Laravel\Module> $availableModules, callable $permissionChecker, \Illuminate\Http\Request $request)
 *
 * @see ViewComposerService
 */
final class ViewComposer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ViewComposerService::class;
    }
}

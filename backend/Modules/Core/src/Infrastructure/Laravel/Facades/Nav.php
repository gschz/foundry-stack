<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\Infrastructure\Laravel\Services\NavigationBuilderService;

/**
 * Facade para construir navegación y breadcrumbs (Módulo Core).
 *
 * @method static array<int, array<string, mixed>> buildNavigation(string $navType, array<int, array<string, mixed>> $itemsConfig, callable $permissionChecker, string $moduleSlug, ?string $functionalName = null)
 * @method static array<string, mixed> assembleNavigationStructure(callable $permissionChecker, ?string $moduleSlug = null, array<int, array<string, mixed>> $contextualItemsConfig = [], $user = null, ?string $functionalName = null, ?string $routeSuffix = null, array<string, mixed> $routeParams = [], array<string, mixed> $viewData = [])
 * @method static array<int, array<string, mixed>> buildNavItems(list<\Nwidart\Modules\Laravel\Module> $modules, callable $permissionChecker)
 * @method static array<int, array<string, mixed>> buildModuleNavItems(list<\Nwidart\Modules\Laravel\Module> $modules, callable $permissionChecker)
 * @method static array<int, array<string, mixed>> buildModuleCards(list<\Nwidart\Modules\Laravel\Module> $allModules, list<\Nwidart\Modules\Laravel\Module> $accessibleModules = [])
 * @method static array<int, array<string, mixed>> buildGlobalNavItems(array<int, array<string, mixed>> $itemsConfig, callable $permissionChecker)
 * @method static array<int, array{title: string, href: string}> buildConfiguredBreadcrumbs(string $moduleSlug, string $routeSuffix, array<string, mixed> $routeParams = [], array<string, mixed> $viewData = [])
 * @method static bool isCurrentRoute(string $routeName)
 *
 * @see NavigationBuilderService
 */
final class Nav extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NavigationBuilderService::class;
    }
}

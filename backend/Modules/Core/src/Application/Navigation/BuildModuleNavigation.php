<?php

declare(strict_types=1);

namespace Modules\Core\Application\Navigation;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\ModuleRegistryInterface;
use Nwidart\Modules\Laravel\Module;

final readonly class BuildModuleNavigation
{
    public function __construct(
        private ModuleRegistryInterface $moduleRegistry
    ) {
        //
    }

    /**
     * Construye los ítems de navegación para la barra lateral.
     *
     * @param  array<Module>  $modules
     * @return array<int, array<string, mixed>>
     */
    public function buildNavItems(
        array $modules,
        callable $permissionChecker
    ): array {
        $navItems = [];
        $totalModules = count($modules);
        $deniedCount = 0;
        $includedMain = 0;
        $includedModule = 0;

        foreach ($modules as $module) {
            $moduleName = mb_strtolower($module->getName());
            $config = $this->moduleRegistry->getModuleConfig($moduleName);

            if (! $this->shouldShowInNav($config)) {
                continue;
            }

            /** @var array<string, mixed> $navItem */
            $navItem = $config['nav_item'];
            $permission = $config['base_permission'] ?? null;
            $allowed = ! $permission || $permissionChecker($permission);

            if ($allowed) {
                $routeName = isset($navItem['route_name']) && is_string($navItem['route_name'])
                    ? $navItem['route_name']
                    : null;

                $item = [
                    'title' => isset($config['functional_name']) && is_string($config['functional_name'])
                        ? $config['functional_name']
                        : $moduleName,
                    'href' => $routeName !== null ? $this->generateRoute($routeName) : '#',
                    'icon' => isset($navItem['icon']) && is_string($navItem['icon']) ? $navItem['icon'] : null,
                    'current' => $routeName !== null && $this->isCurrentRoute($routeName),
                ];

                if ($navItem['show_in_main_nav'] ?? false) {
                    $navItems[] = $item;
                    $includedMain++;
                } else {
                    $includedModule++;
                }
            } else {
                $this->recordNavPermissionDenial(
                    is_string($permission) ? $permission : null,
                    $moduleName
                );
                $deniedCount++;
            }
        }

        Log::channel('domain_navigation')->info('nav_items_build', [
            'total_modules' => $totalModules,
            'included_main' => $includedMain,
            'included_module' => $includedModule,
            'denied' => $deniedCount,
        ]);

        return $navItems;
    }

    /**
     * Construye los ítems de navegación para la lista de módulos.
     *
     * @param  array<Module>  $modules
     * @return array<int, array<string, mixed>>
     */
    public function buildModuleNavItems(
        array $modules,
        callable $permissionChecker
    ): array {
        $moduleItems = [];
        $totalModules = count($modules);
        $deniedCount = 0;
        $includedCount = 0;

        foreach ($modules as $module) {
            $moduleName = mb_strtolower($module->getName());
            $config = $this->moduleRegistry->getModuleConfig($moduleName);

            if (! $this->shouldShowInNav($config)) {
                continue;
            }

            /** @var array<string, mixed> $navItem */
            $navItem = $config['nav_item'];
            $permission = $config['base_permission'] ?? null;
            $allowed = ! $permission || $permissionChecker($permission);
            $showInMainNav = $navItem['show_in_main_nav'] ?? false;

            if ($allowed && ! $showInMainNav) {
                $routeName = isset($navItem['route_name']) && is_string($navItem['route_name'])
                    ? $navItem['route_name'] : null;

                $moduleItems[] = [
                    'title' => isset($config['functional_name']) && is_string($config['functional_name'])
                        ? $config['functional_name'] : $moduleName,
                    'href' => $routeName !== null
                        ? $this->generateRoute($routeName) : '#',
                    'icon' => isset($navItem['icon']) && is_string($navItem['icon'])
                        ? $navItem['icon'] : null,
                    'current' => $routeName !== null && $this->isCurrentRoute($routeName),
                ];
                $includedCount++;
            } elseif (! $allowed) {
                $this->recordNavPermissionDenial(is_string($permission) ? $permission : null, $moduleName);
                $deniedCount++;
            }
        }

        Log::channel('domain_navigation')->info('module_nav_build', [
            'total_modules' => $totalModules,
            'included' => $includedCount,
            'denied' => $deniedCount,
        ]);

        return $moduleItems;
    }

    /**
     * Construye las tarjetas de módulos para el dashboard.
     *
     * @param  array<Module>  $allModules
     * @param  array<Module>  $accessibleModules
     * @return array<int, array<string, mixed>>
     */
    public function buildModuleCards(
        array $allModules,
        array $accessibleModules = []
    ): array {
        $moduleCards = [];
        $accessibleNames = [];
        foreach ($accessibleModules as $am) {
            $accessibleNames[mb_strtolower($am->getName())] = true;
        }

        foreach ($allModules as $module) {
            $moduleNameLower = mb_strtolower($module->getName());
            $config = $this->moduleRegistry->getModuleConfig($moduleNameLower);

            if (! $this->shouldShowInNav($config)) {
                continue;
            }

            /** @var array<string, mixed> $navItem */
            $navItem = $config['nav_item'];

            $routeName = isset($navItem['route_name']) && is_string($navItem['route_name'])
                ? $navItem['route_name']
                : null;
            $canAccess = isset($accessibleNames[$moduleNameLower]);

            $moduleCards[] = [
                'name' => isset($config['functional_name']) && is_string($config['functional_name'])
                    ? $config['functional_name']
                    : $module->getName(),
                'description' => $config['description'] ?? '',
                'href' => $routeName !== null ? $this->generateRoute($routeName) : '#',
                'icon' => isset($navItem['icon']) && is_string($navItem['icon'])
                    ? $navItem['icon']
                    : null,
                'canAccess' => $canAccess,
            ];
        }

        return $moduleCards;
    }

    /**
     * Determina si el módulo debe mostrarse en la navegación.
     *
     * @param  array<string, mixed>  $config
     */
    private function shouldShowInNav(array $config): bool
    {
        if (! isset($config['nav_item']) || ! is_array($config['nav_item'])) {
            return false;
        }

        return (bool) ($config['nav_item']['show_in_nav'] ?? false);
    }

    private function generateRoute(string $routeName): string
    {
        try {
            if (Route::has($routeName)) {
                return route($routeName);
            }
        } catch (Exception) {
            // Log suppressed here to match original service behavior or simplicity
        }

        return '#';
    }

    private function isCurrentRoute(string $routeName): bool
    {
        $currentRoute = Route::currentRouteName();
        if (! $currentRoute) {
            return false;
        }

        return $currentRoute === $routeName
            || str_starts_with($currentRoute, $routeName.'.');
    }

    private function recordNavPermissionDenial(
        ?string $permission,
        ?string $moduleSlug = null
    ): void {
        if (! is_string($permission) || $permission === '') {
            return;
        }

        Cache::increment('metrics:navigation:denied:total');
        Cache::increment('metrics:navigation:denied:permission:'.$permission);
        if ($moduleSlug) {
            Cache::increment('metrics:navigation:denied:module:'.$moduleSlug);
        }

        Log::channel('domain_navigation')->info('permission_denied', [
            'permission' => $permission,
            'module' => $moduleSlug,
            'context' => 'module_nav',
        ]);
    }
}

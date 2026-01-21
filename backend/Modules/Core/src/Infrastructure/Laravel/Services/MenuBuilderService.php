<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Modules\Core\Application\Menu\AssembleMenu;
use Modules\Core\Application\Menu\BuildAddonMenu;
use Modules\Core\Application\Menu\BuildBreadcrumbs;
use Modules\Core\Application\Menu\BuildContextualMenu;
use Modules\Core\Application\Menu\BuildGlobalMenu;
use Modules\Core\Contracts\MenuBuilderInterface;
use Modules\Core\Domain\Menu\MenuConfigResolver;

/**
 * Servicio adaptador para construcción de navegación y breadcrumbs.
 *
 * Implementa los contratos de menú del Core y delega en casos de uso
 * de Application. Registra latencia de armado para observabilidad.
 */
final readonly class MenuBuilderService implements MenuBuilderInterface
{
    public function __construct(
        private AssembleMenu $assembler,
        private BuildGlobalMenu $globalBuilder,
        private BuildAddonMenu $moduleBuilder,
        private BuildContextualMenu $contextualBuilder,
        private BuildBreadcrumbs $breadcrumbsBuilder,
        private MenuConfigResolver $configResolver
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function buildNavigation(
        string $navType,
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName = null
    ): array {
        return $this->contextualBuilder->execute(
            $navType,
            $itemsConfig,
            $permissionChecker,
            $moduleSlug,
            $functionalName
        );
    }

    /**
     * {@inheritDoc}
     */
    public function buildContextualNavItems(
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName = null
    ): array {
        return $this->contextualBuilder->execute(
            self::NAV_TYPE_CONTEXTUAL,
            $itemsConfig,
            $permissionChecker,
            $moduleSlug,
            $functionalName
        );
    }

    /**
     * {@inheritDoc}
     */
    public function buildPanelItems(
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName = null
    ): array {
        return $this->contextualBuilder->execute(
            self::NAV_TYPE_PANEL,
            $itemsConfig,
            $permissionChecker,
            $moduleSlug,
            $functionalName
        );
    }

    /**
     * {@inheritDoc}
     */
    public function buildNavItems(
        array $modules,
        callable $permissionChecker
    ): array {
        return $this->moduleBuilder->buildNavItems(
            $modules,
            $permissionChecker
        );
    }

    /**
     * {@inheritDoc}
     */
    public function buildModuleNavItems(
        array $modules,
        callable $permissionChecker
    ): array {
        return $this->moduleBuilder->buildModuleNavItems(
            $modules,
            $permissionChecker
        );
    }

    /**
     * {@inheritDoc}
     */
    public function buildModuleCards(
        array $allModules,
        array $accessibleModules = []
    ): array {
        return $this->moduleBuilder->buildModuleCards(
            $allModules,
            $accessibleModules
        );
    }

    /**
     * {@inheritDoc}
     */
    public function buildConfiguredBreadcrumbs(
        string $moduleSlug,
        string $routeSuffix,
        array $routeParams = [],
        array $viewData = []
    ): array {
        return $this->breadcrumbsBuilder->execute(
            $moduleSlug,
            $routeSuffix,
            $routeParams,
            $viewData
        );
    }

    /**
     * {@inheritDoc}
     */
    public function buildGlobalNavItems(
        array $itemsConfig,
        callable $permissionChecker
    ): array {
        return $this->globalBuilder->execute($itemsConfig, $permissionChecker);
    }

    /**
     * {@inheritDoc}
     */
    public function resolveConfigReferences(
        $config,
        array $moduleConfig
    ): mixed {
        return $this->configResolver->resolve($config, $moduleConfig);
    }

    /**
     * Compone la estructura completa de navegación y registra latencia.
     *
     * {@inheritDoc}
     */
    public function assembleNavigationStructure(
        callable $permissionChecker,
        ?string $moduleSlug = null,
        array $contextualItemsConfig = [],
        $user = null,
        ?string $functionalName = null,
        ?string $routeSuffix = null,
        array $routeParams = [],
        array $viewData = []
    ): array {
        $t0 = microtime(true);

        $result = $this->assembler->execute(
            $permissionChecker,
            $moduleSlug,
            $contextualItemsConfig,
            $user,
            $functionalName,
            $routeSuffix,
            $routeParams,
            $viewData
        );

        $durationMs = (microtime(true) - $t0) * 1000;
        $mainCount = is_array($result['mainNavItems'] ?? null)
            ? count($result['mainNavItems'])
            : 0;
        $moduleCount = is_array($result['moduleNavItems'] ?? null)
            ? count($result['moduleNavItems'])
            : 0;
        $contextualCount = is_array($result['contextualNavItems'] ?? null)
            ? count($result['contextualNavItems'])
            : 0;
        $globalCount = is_array($result['globalNavItems'] ?? null)
            ? count($result['globalNavItems'])
            : 0;
        $breadcrumbsCount = is_array($result['breadcrumbs'] ?? null)
            ? count($result['breadcrumbs'])
            : 0;

        Log::channel('domain_navigation')->info('nav_build_latency', [
            'module_slug' => $moduleSlug,
            'route_suffix' => $routeSuffix,
            'duration_ms' => round($durationMs, 2),
            'main_count' => $mainCount,
            'module_count' => $moduleCount,
            'contextual_count' => $contextualCount,
            'global_count' => $globalCount,
            'breadcrumbs_count' => $breadcrumbsCount,
        ]);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function isCurrentRoute(string $routeName): bool
    {
        $currentRoute = Route::currentRouteName();
        if (! $currentRoute) {
            return false;
        }

        return $currentRoute === $routeName
            || str_starts_with($currentRoute, $routeName.'.');
    }
}

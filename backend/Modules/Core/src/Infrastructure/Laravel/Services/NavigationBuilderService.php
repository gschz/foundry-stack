<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Illuminate\Support\Facades\Route;
use Modules\Core\Application\Navigation\AssembleNavigation;
use Modules\Core\Application\Navigation\BuildBreadcrumbs;
use Modules\Core\Application\Navigation\BuildContextualNavigation;
use Modules\Core\Application\Navigation\BuildGlobalNavigation;
use Modules\Core\Application\Navigation\BuildModuleNavigation;
use Modules\Core\Contracts\NavigationBuilderInterface;
use Modules\Core\Domain\Navigation\NavigationConfigResolver;

final readonly class NavigationBuilderService implements NavigationBuilderInterface
{
    public function __construct(
        private AssembleNavigation $assembler,
        private BuildGlobalNavigation $globalBuilder,
        private BuildModuleNavigation $moduleBuilder,
        private BuildContextualNavigation $contextualBuilder,
        private BuildBreadcrumbs $breadcrumbsBuilder,
        private NavigationConfigResolver $configResolver
    ) {
        //
    }

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

    public function buildNavItems(
        array $modules,
        callable $permissionChecker
    ): array {
        return $this->moduleBuilder->buildNavItems(
            $modules,
            $permissionChecker
        );
    }

    public function buildModuleNavItems(
        array $modules,
        callable $permissionChecker
    ): array {
        return $this->moduleBuilder->buildModuleNavItems(
            $modules,
            $permissionChecker
        );
    }

    public function buildModuleCards(
        array $allModules,
        array $accessibleModules = []
    ): array {
        return $this->moduleBuilder->buildModuleCards(
            $allModules,
            $accessibleModules
        );
    }

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

    public function buildGlobalNavItems(
        array $itemsConfig,
        callable $permissionChecker
    ): array {
        return $this->globalBuilder->execute($itemsConfig, $permissionChecker);
    }

    public function resolveConfigReferences(
        $config,
        array $moduleConfig
    ): mixed {
        // Compatibility wrapper
        return $this->configResolver->resolve($config, $moduleConfig);
    }

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
        return $this->assembler->execute(
            $permissionChecker,
            $moduleSlug,
            $contextualItemsConfig,
            $user,
            $functionalName,
            $routeSuffix,
            $routeParams,
            $viewData
        );
    }

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

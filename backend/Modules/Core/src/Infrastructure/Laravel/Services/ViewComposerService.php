<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Core\Contracts\ModuleRegistryInterface;
use Modules\Core\Contracts\NavigationBuilderInterface;
use Modules\Core\Contracts\ViewComposerInterface;

/**
 * Servicio para componer y preparar datos para las vistas.
 * Implementación concreta para Laravel/Inertia.
 */
final readonly class ViewComposerService implements ViewComposerInterface
{
    public function __construct(
        private NavigationBuilderInterface $navigationService,
        private ModuleRegistryInterface $moduleRegistry,
    ) {
        //
    }

    public function prepareModuleViewData(
        string $moduleSlug,
        array $panelItemsConfig,
        callable $permissionChecker,
        string $functionalName,
        ?array $stats = null,
        array $data = []
    ): array {
        $isList = array_is_list($panelItemsConfig);

        $normalizedPanelItemsConfig = $isList
            ? $panelItemsConfig
            : [$panelItemsConfig];

        // Asegurar que cada ítem tenga llaves string para cumplir el contrato
        $normalizedPanelItemsConfig = array_map(
            static function ($item): array {
                if (! is_array($item)) {
                    return [];
                }

                $assoc = [];
                foreach ($item as $k => $v) {
                    $assoc[(string) $k] = $v;
                }

                return $assoc;
            },
            $normalizedPanelItemsConfig
        );

        $panelItems = $this->navigationService
            ->buildPanelItems(
                itemsConfig: $normalizedPanelItemsConfig,
                permissionChecker: $permissionChecker,
                moduleSlug: $moduleSlug,
                functionalName: $functionalName
            );

        // Obtener descripción desde el config del módulo
        $moduleConfig = $this->moduleRegistry->getModuleConfig($moduleSlug);
        $moduleDescription = $moduleConfig['description'] ?? null;

        return [
            ...[
                'panelItems' => $panelItems,
                'stats' => (object) ($stats ?? []),
                'pageTitle' => $functionalName,
                'description' => $moduleDescription,
                'flash' => $this->getFlashMessages(request()),
            ],
            ...$data,
        ];
    }

    public function composeModuleViewContext(
        string $moduleSlug,
        array $panelItemsConfig,
        array $contextualNavItemsConfig,
        callable $permissionChecker,
        $user,
        ?string $functionalName = null,
        array $data = [],
        ?array $stats = null,
        ?string $routeSuffix = null,
        array $routeParams = []
    ): array {
        // Normalizar nombre funcional y obtener descripción desde config del módulo
        $moduleConfig = $this->moduleRegistry->getModuleConfig($moduleSlug);
        $fn = $moduleConfig['functional_name'] ?? null;
        $functionalName = is_string($functionalName)
            ? $functionalName
            : (is_string($fn) ? $fn : ucfirst($moduleSlug));
        $moduleDescription = $moduleConfig['description'] ?? null;

        // Obtener todos los elementos de navegación
        $navigationElements = $this->navigationService
            ->assembleNavigationStructure(
                permissionChecker: $permissionChecker,
                moduleSlug: $moduleSlug,
                contextualItemsConfig: $contextualNavItemsConfig,
                user: $user,
                functionalName: $functionalName,
                routeSuffix: $routeSuffix,
                routeParams: $routeParams,
                viewData: $data
            );

        // Construir ítems del panel
        $panelItems = $this->navigationService
            ->buildPanelItems(
                itemsConfig: $panelItemsConfig,
                permissionChecker: $permissionChecker,
                moduleSlug: $moduleSlug,
                functionalName: $functionalName
            );

        // Combinar todos los datos
        return [
            ...[
                'panelItems' => $panelItems,
                'mainNavItems' => $navigationElements['mainNavItems'] ?? [],
                'moduleNavItems' => $navigationElements['moduleNavItems'] ?? [],
                'contextualNavItems' => $navigationElements['contextualNavItems'],
                'globalNavItems' => $navigationElements['globalNavItems'],
                'breadcrumbs' => $navigationElements['breadcrumbs'],
                'stats' => (object) ($stats ?? []),
                'pageTitle' => $functionalName,
                'description' => $moduleDescription,
                'flash' => $this->getFlashMessages(request()),
            ],
            ...$data,
        ];
    }

    public function composeDashboardViewContext(
        $user,
        array $availableModules,
        callable $permissionChecker,
        Request $request
    ): array {
        /** @var \Modules\Core\Infrastructure\Eloquent\Models\StaffUser|null $user */
        /** @var array<\Nwidart\Modules\Laravel\Module> $availableModules */

        // Construir los ítems de navegación principales
        $mainNavItems = $this->navigationService->buildNavItems(
            $availableModules,
            $permissionChecker
        );

        // Construir items de navegación global
        $globalItemsConfig = $this->moduleRegistry->getGlobalNavItems($user);
        $globalNavItems = $this->navigationService->buildGlobalNavItems(
            $globalItemsConfig,
            $permissionChecker
        );

        // Construir tarjetas de módulos (disponibles y restringidos)
        $allModules = $this->moduleRegistry->getAllEnabledModules();
        $moduleCards = $this->navigationService->buildModuleCards(
            $allModules,
            $availableModules
        );

        $accessibleModulesCards = array_values(array_filter(
            $moduleCards,
            static fn (array $card): bool => ($card['canAccess'] ?? false) === true
        ));

        $restrictedModulesCards = array_values(array_filter(
            $moduleCards,
            static fn (array $card): bool => ($card['canAccess'] ?? false) === false
        ));

        return [
            'mainNavItems' => $mainNavItems,
            'globalNavItems' => $globalNavItems,
            'modules' => $moduleCards,
            'accessibleModules' => $accessibleModulesCards,
            'restrictedModules' => $restrictedModulesCards,
            'flash' => $this->getFlashMessages($request),
        ];
    }

    public function renderModuleView(
        string $view,
        string $moduleViewPath,
        array $data = []
    ): InertiaResponse {
        return Inertia::render(
            sprintf('modules/%s/%s', $moduleViewPath, $view),
            $data
        );
    }

    public function getFlashMessages(Request $request): array
    {
        return [
            'success' => $request->session()->get('success'),
            'error' => $request->session()->get('error'),
            'info' => $request->session()->get('info'),
            'warning' => $request->session()->get('warning'),
            'credentials' => $request->session()->get('credentials'),
        ];
    }
}

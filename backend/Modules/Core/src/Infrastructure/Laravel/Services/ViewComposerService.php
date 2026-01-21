<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Core\Contracts\AddonRegistryInterface;
use Modules\Core\Contracts\MenuBuilderInterface;
use Modules\Core\Contracts\ViewComposerInterface;

/**
 * Servicio para componer y preparar datos para las vistas (Laravel/Inertia).
 *
 * Estandariza props compartidas y estructura de navegación con caché
 * versionada por usuario/ruta y estado de módulos.
 */
final readonly class ViewComposerService implements ViewComposerInterface
{
    public function __construct(
        private MenuBuilderInterface $navigationService,
        private AddonRegistryInterface $moduleRegistry,
    ) {
        //
    }

    /**
     * {@inheritDoc}
     *
     * Nota: Normaliza items del panel asegurando claves string.
     */
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
        $moduleConfig = $this->moduleRegistry->getAddonConfig($moduleSlug);
        $moduleDescription = $moduleConfig['description'] ?? null;

        $statsList = is_array($stats)
            ? array_values($stats)
            : [];

        return [
            ...[
                'panelItems' => $panelItems,
                'stats' => $statsList,
                'pageTitle' => $functionalName,
                'description' => $moduleDescription,
                'flash' => $this->getFlashMessages(request()),
            ],
            ...$data,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * Nota: Aplica caché versionada con claves que incluyen:
     * usuario, módulo, routeSuffix, nav_version, mtime de módulos y perm_version.
     */
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
        $moduleConfig = $this->moduleRegistry->getAddonConfig($moduleSlug);
        $fn = $moduleConfig['functional_name'] ?? null;
        $functionalName = is_string($functionalName)
            ? $functionalName
            : (is_string($fn)
                ? $fn
                : ucfirst($moduleSlug)
            );
        $moduleDescription = $moduleConfig['description'] ?? null;

        // Obtener todos los elementos de navegación
        $prefixRaw = config('core.cache.nav_cache_prefix');
        $prefix = is_string($prefixRaw) && $prefixRaw !== ''
            ? $prefixRaw
            : 'core:nav:';
        $versionKeyRaw = config('core.cache.nav_version_key');
        $versionKey = is_string($versionKeyRaw) && $versionKeyRaw !== ''
            ? $versionKeyRaw
            : 'core.nav_version';
        $navVersionRaw = Cache::get($versionKey, 1);
        $navVersion = is_int($navVersionRaw)
            ? $navVersionRaw
            : (is_numeric($navVersionRaw)
                ? (int) $navVersionRaw
                : 1
            );
        $ttlRaw = config('core.cache.nav_assembled_ttl_seconds');
        $ttl = is_numeric($ttlRaw)
            ? (int) $ttlRaw
            : 300;
        $userIdRaw = is_object($user) && method_exists($user, 'getAuthIdentifier')
            ? $user->getAuthIdentifier()
            : null;
        $userId = is_string($userIdRaw) || is_int($userIdRaw)
            ? (string) $userIdRaw
            : 'anonymous';
        $permVersionRaw = $userId !== 'anonymous'
            ? Cache::get('user.'.$userId.'.perm_version', 0)
            : 0;
        $permVersion = is_int($permVersionRaw)
            ? $permVersionRaw
            : (is_numeric($permVersionRaw)
                ? (int) $permVersionRaw
                : 0
            );
        $suffix = is_string($routeSuffix) && $routeSuffix !== ''
            ? $routeSuffix
            : 'panel';
        $statusesFileRaw = config('modules.activators.file.statuses-file');
        $modulesStatusesPath = is_string($statusesFileRaw) && $statusesFileRaw !== ''
            ? $statusesFileRaw
            : base_path('modules_statuses.json');
        $modulesMtime = file_exists($modulesStatusesPath)
            ? (int) @filemtime($modulesStatusesPath)
            : 0;

        $cacheKey = sprintf(
            '%s%s:%s:%s:%d:%d:%d',
            $prefix,
            $userId,
            $moduleSlug,
            $suffix,
            $navVersion,
            $modulesMtime,
            $permVersion
        );

        $navigationElements = Cache::remember(
            $cacheKey,
            $ttl,
            fn (): array => $this->navigationService->assembleNavigationStructure(
                permissionChecker: $permissionChecker,
                moduleSlug: $moduleSlug,
                contextualItemsConfig: $contextualNavItemsConfig,
                user: $user,
                functionalName: $functionalName,
                routeSuffix: $suffix,
                routeParams: $routeParams,
                viewData: $data
            )
        );

        $navigationElements = is_array($navigationElements)
            ? $navigationElements : [
                'mainNavItems' => [],
                'moduleNavItems' => [],
                'contextualNavItems' => [],
                'globalNavItems' => [],
                'breadcrumbs' => [],
            ];

        // Construir ítems del panel
        $panelItems = $this->navigationService
            ->buildPanelItems(
                itemsConfig: $panelItemsConfig,
                permissionChecker: $permissionChecker,
                moduleSlug: $moduleSlug,
                functionalName: $functionalName
            );

        $statsList = is_array($stats)
            ? array_values($stats)
            : [];

        // Combinar todos los datos
        return [
            ...[
                'panelItems' => $panelItems,
                'mainNavItems' => $navigationElements['mainNavItems'] ?? [],
                'moduleNavItems' => $navigationElements['moduleNavItems'] ?? [],
                'contextualNavItems' => $navigationElements['contextualNavItems'],
                'globalNavItems' => $navigationElements['globalNavItems'],
                'breadcrumbs' => $navigationElements['breadcrumbs'],
                'stats' => $statsList,
                'pageTitle' => $functionalName,
                'description' => $moduleDescription,
                'flash' => $this->getFlashMessages(request()),
            ],
            ...$data,
        ];
    }

    /**
     * {@inheritDoc}
     */
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

        $moduleNavItems = $this->navigationService->buildModuleNavItems(
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
        $allModules = $this->moduleRegistry->getAllEnabledAddons();
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
            'pageTitle' => 'Dashboard',
            'description' => 'Panel principal del sistema interno.',
            'breadcrumbs' => [[
                'title' => 'Dashboard',
                'href' => route('internal.staff.dashboard'),
            ]],
            'mainNavItems' => $mainNavItems,
            'moduleNavItems' => $moduleNavItems,
            'contextualNavItems' => [],
            'globalNavItems' => $globalNavItems,
            'modules' => $moduleCards,
            'accessibleModules' => $accessibleModulesCards,
            'restrictedModules' => $restrictedModulesCards,
            'flash' => $this->getFlashMessages($request),
        ];
    }

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
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

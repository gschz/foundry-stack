<?php

declare(strict_types=1);

namespace Modules\Core\Application\Navigation;

use Illuminate\Support\Facades\Log;
use Modules\Core\Contracts\ModuleRegistryInterface;
use Modules\Core\Contracts\NavigationBuilderInterface;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

final readonly class AssembleNavigation
{
    /**
     * Constructor de la clase AssembleNavigation.
     *
     * @param  ModuleRegistryInterface  $moduleRegistry  Servicio de registro de módulos.
     * @param  BuildModuleNavigation  $buildModule  Servicio para construir la navegación de módulos.
     * @param  BuildContextualNavigation  $buildContextual  Servicio para construir la navegación contextual.
     * @param  BuildBreadcrumbs  $buildBreadcrumbs  Servicio para construir los breadcrumbs.
     */
    public function __construct(
        private ModuleRegistryInterface $moduleRegistry,
        private BuildModuleNavigation $buildModule,
        private BuildContextualNavigation $buildContextual,
        private BuildBreadcrumbs $buildBreadcrumbs
    ) {
        //
    }

    /**
     * Ejecuta el ensamblaje de toda la estructura de navegación.
     *
     * @param  callable  $permissionChecker  Función para verificar permisos.
     * @param  string|null  $moduleSlug  Slug del módulo actual.
     * @param  array<int, array<string, mixed>>  $contextualItemsConfig  Configuración de ítems contextuales.
     * @param  mixed  $user  Usuario autenticado (puede ser StaffUser o null).
     * @param  string|null  $functionalName  Nombre funcional del módulo.
     * @param  string|null  $routeSuffix  Sufijo de la ruta actual.
     * @param  array<string, mixed>  $routeParams  Parámetros de la ruta.
     * @param  array<string, mixed>  $viewData  Datos de la vista.
     * @return array<string, mixed> Estructura completa de navegación.
     */
    public function execute(
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
        $req = request();
        $cacheMap = (array) $req->attributes->get('navigation_cache', []);

        $uid = 'guest';
        $staffUser = null;
        if ($user instanceof StaffUser) {
            $staffUser = $user;
            $rawId = $user->getAuthIdentifier();
            $uid = is_string($rawId) ? $rawId : (is_int($rawId) ? (string) $rawId : 'guest');
        }

        // Clave de caché
        $key = implode('|', [
            $moduleSlug ?? '',
            $routeSuffix ?? '',
            $uid,
            md5((string) json_encode($routeParams)),
            md5((string) json_encode($viewData)),
        ]);

        if (isset($cacheMap[$key]) && is_array($cacheMap[$key])) {
            /** @var array<string, mixed> $cachedResult */
            $cachedResult = $cacheMap[$key];
            $this->logAssembly($moduleSlug, $routeSuffix, $uid, $cachedResult, true, $t0);

            return $cachedResult;
        }

        // 1. Navegación Global
        $globalNavItems = $this->moduleRegistry->getGlobalNavItems($staffUser);

        // 2. Navegación de Módulos
        $modules = $this->moduleRegistry->getAccessibleModules($staffUser);

        $mainNavItems = $this->buildModule->buildNavItems(
            $modules,
            $permissionChecker
        );
        $moduleNavItems = $this->buildModule->buildModuleNavItems(
            $modules,
            $permissionChecker
        );

        // 3. Navegación Contextual
        $contextualNavItems = [];
        if ($contextualItemsConfig !== []) {
            $contextualNavItems = $this->buildContextual->execute(
                NavigationBuilderInterface::NAV_TYPE_CONTEXTUAL,
                $contextualItemsConfig,
                $permissionChecker,
                $moduleSlug ?? 'core',
                $functionalName
            );
        }

        // 4. Migas de pan (Breadcrumbs)
        $breadcrumbs = [];
        if ($moduleSlug && $routeSuffix) {
            $breadcrumbs = $this->buildBreadcrumbs->execute(
                $moduleSlug,
                $routeSuffix,
                $routeParams,
                $viewData
            );
        } elseif ($moduleSlug && $contextualNavItems !== []) {
            // Fallback usando ítems contextuales
            $breadcrumbs = $this->buildBreadcrumbs->buildFromContextual(
                $contextualNavItems,
                $moduleSlug,
                \Illuminate\Support\Facades\Route::currentRouteName() ?? ''
            );
        }

        /** @var array<string, mixed> $result */
        $result = [
            'mainNavItems' => $mainNavItems,
            'moduleNavItems' => $moduleNavItems,
            'contextualNavItems' => $contextualNavItems,
            'globalNavItems' => $globalNavItems,
            'breadcrumbs' => $breadcrumbs,
        ];

        $this->logAssembly(
            $moduleSlug,
            $routeSuffix,
            $uid,
            $result,
            false,
            $t0
        );

        return $result;
    }

    /**
     * Registra métricas sobre el ensamblaje de la navegación.
     *
     * @param  string|null  $slug  Slug del módulo.
     * @param  string|null  $suffix  Sufijo de la ruta.
     * @param  string  $uid  Identificador del usuario.
     * @param  array<string, mixed>  $data  Datos generados.
     * @param  bool  $hit  Indica si hubo acierto en caché.
     * @param  float  $t0  Tiempo de inicio en microsegundos.
     */
    private function logAssembly(
        ?string $slug,
        ?string $suffix,
        string $uid,
        array $data,
        bool $hit,
        float $t0
    ): void {
        $durationMs = (microtime(true) - $t0) * 1000;

        /** @var array<mixed> $main */
        $main = $data['mainNavItems'] ?? [];
        /** @var array<mixed> $mod */
        $mod = $data['moduleNavItems'] ?? [];
        /** @var array<mixed> $ctx */
        $ctx = $data['contextualNavItems'] ?? [];
        /** @var array<mixed> $glob */
        $glob = $data['globalNavItems'] ?? [];
        /** @var array<mixed> $crumbs */
        $crumbs = $data['breadcrumbs'] ?? [];

        Log::channel('domain_navigation')->info('navigation_assemble', [
            'module_slug' => $slug,
            'route_suffix' => $suffix,
            'user_id' => $uid,
            'counts' => [
                'main' => count($main),
                'module' => count($mod),
                'contextual' => count($ctx),
                'global' => count($glob),
                'breadcrumbs' => count($crumbs),
            ],
            'cache_hit' => $hit,
            'duration_ms' => $durationMs,
        ]);
    }
}

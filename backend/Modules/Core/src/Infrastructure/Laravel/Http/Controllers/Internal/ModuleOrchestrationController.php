<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Response as InertiaResponse;
use Modules\Core\Contracts\AddonRegistryInterface;
use Modules\Core\Contracts\MenuBuilderInterface;
use Modules\Core\Contracts\ViewComposerInterface;
use Modules\Core\Infrastructure\Laravel\Traits\PermissionVerifier;
use ReflectionClass;
use Throwable;

/**
 * @deprecated Usar Modules\Core\Contracts\ModuleOrchestratorInterface en su lugar.
 */
abstract class ModuleOrchestrationController extends Controller
{
    use PermissionVerifier;

    /**
     * Slug del módulo (por convención, derivado del namespace del controlador hijo).
     */
    protected string $moduleSlug = '';

    /**
     * Configuración del módulo (normalmente leída del registro de addons).
     *
     * @var array<string, mixed>
     */
    protected array $moduleConfig = [];

    /**
     * Inicializa dependencias base y carga configuración del módulo.
     *
     * @param  AddonRegistryInterface  $moduleRegistryService  Registro de módulos/addons y sus configuraciones.
     * @param  ViewComposerInterface  $viewComposerService  Servicio de composición de props y renderizado Inertia.
     * @param  MenuBuilderInterface|null  $navigationService  Servicio de menú/breadcrumbs (opcional).
     */
    public function __construct(
        protected readonly AddonRegistryInterface $moduleRegistryService,
        protected readonly ViewComposerInterface $viewComposerService,
        protected readonly ?MenuBuilderInterface $navigationService = null
    ) {
        $this->detectModuleAndLoadConfig();
    }

    /**
     * Renderiza el panel principal del módulo.
     *
     * No debe ser sobrescrito: la personalización se realiza vía métodos de extensión.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException Si el usuario no está autenticado.
     */
    final public function showModulePanel(Request $request): InertiaResponse
    {
        $additional = ['stats' => $this->getModuleStats()];

        $extras = $this->getAdditionalPanelData();

        if ($extras !== []) {
            $additional = array_merge($additional, $extras);
        }

        return $this->prepareAndRenderModuleView(
            view: 'index',
            request: $request,
            additionalData: $additional
        );
    }

    /**
     * Devuelve el slug del módulo.
     */
    protected function getModuleSlug(): string
    {
        return $this->moduleSlug;
    }

    /**
     * Devuelve el nombre funcional del módulo para UI (título de página).
     */
    protected function getFunctionalName(): string
    {
        $name = $this->moduleConfig['functional_name'] ?? '';

        return is_string($name) ? $name : '';
    }

    /**
     * Devuelve el directorio base de vistas Inertia del módulo.
     */
    protected function getInertiaViewDirectory(): string
    {
        $dir = $this->moduleConfig['inertia_view_directory']
            ?? $this->moduleSlug;

        return is_string($dir) ? $dir : $this->moduleSlug;
    }

    /**
     * Devuelve el/los permisos base requeridos para acceder al módulo.
     *
     * @return array<int, string>|string
     */
    protected function getBaseAccessPermission(): string|array
    {
        $perm = $this->moduleConfig['base_permission'] ?? '';

        if (is_string($perm)) {
            return $perm;
        }

        if (is_array($perm)) {
            return array_values(array_filter(
                $perm,
                is_string(...)
            ));
        }

        return '';
    }

    /**
     * Devuelve el guard configurado para autenticación del módulo.
     */
    protected function getAuthGuard(): string
    {
        $guard = $this->moduleConfig['auth_guard'] ?? '';

        return is_string($guard) ? $guard : '';
    }

    /**
     * Devuelve el usuario autenticado usando el guard configurado.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function getAuthenticatedUser()
    {
        return Auth::guard($this->getAuthGuard())->user();
    }

    /**
     * Devuelve configuración de items del panel (normalmente declarativa desde config del módulo).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getPanelItemsConfig(): array
    {
        $panelConfig = $this->moduleConfig['panel_items'] ?? [];
        if (! is_array($panelConfig)) {
            return [];
        }

        $normalized = [];
        foreach ($panelConfig as $item) {
            if (is_array($item)) {
                $normalizedItem = [];
                foreach ($item as $k => $v) {
                    $normalizedItem[(string) $k] = $v;
                }

                /** @var array<string, mixed> $normalizedItem */
                $normalized[] = $normalizedItem;
            }
        }

        /** @var array<int, array<string, mixed>> $normalized */
        return $normalized;
    }

    /**
     * Devuelve configuración de navegación contextual, con fallback a "default".
     *
     * Esta configuración puede incluir referencias ($ref:...) que serán resueltas
     * posteriormente por el builder de menú.
     *
     * @return array<int, mixed>
     */
    protected function getContextualNavItemsConfig(): array
    {
        $navConfigAll = $this->moduleConfig['contextual_nav'] ?? [];

        if (! is_array($navConfigAll)) {
            return [];
        }

        try {
            $currentRequest = request();
            if ($currentRequest->route()) {
                $suffix = $this->extractRouteSuffixFromRequest($currentRequest);
                if (
                    isset($navConfigAll[$suffix])
                    && is_array($navConfigAll[$suffix])
                ) {
                    return array_values($navConfigAll[$suffix]);
                }
            }
        } catch (Throwable) {
        }

        if (
            isset($navConfigAll['default'])
            && is_array($navConfigAll['default'])
        ) {
            return array_values($navConfigAll['default']);
        }

        return [];
    }

    /**
     * Punto de extensión: estadísticas para el panel del módulo.
     *
     * @return array<int, mixed>|null Colección de estadísticas consumibles por el frontend.
     */
    protected function getModuleStats(): ?array
    {
        return null;
    }

    /**
     * Punto de extensión: props adicionales para el panel del módulo.
     *
     * Ejemplo:
     * - 'recentActivity' => [...]
     *
     * @return array<string, mixed>
     */
    protected function getAdditionalPanelData(): array
    {
        return [];
    }

    /**
     * Renderiza una vista Inertia del módulo usando el ViewComposer.
     *
     * @param  array<string, mixed>  $data
     */
    protected function renderModuleView(string $view, array $data = []): InertiaResponse
    {
        return $this->viewComposerService->renderModuleView(
            view: $view,
            moduleViewPath: $this->getInertiaViewDirectory(),
            data: $data
        );
    }

    /**
     * Resuelve referencias declarativas ($ref:...) en config usando el builder de menú.
     *
     * @param  mixed  $config  Config con posibles referencias a resolver.
     * @param  array<string, mixed>  $routeParams  Parámetros de ruta actuales.
     * @return mixed Config resuelta.
     */
    protected function resolveConfigReferences(mixed $config, array $routeParams = []): mixed
    {
        if ($this->navigationService && ! empty($config)) {
            return $this->navigationService->resolveConfigReferences(
                $config,
                $this->moduleConfig
            );
        }

        return $config;
    }

    /**
     * Prepara el contexto completo para una vista del módulo y la renderiza.
     *
     * Responsabilidades:
     * - Resolver panel items y navegación contextual (incluyendo referencias).
     * - Determinar routeSuffix y routeParams para breadcrumbs/navegación.
     * - Delegar al ViewComposer la composición final de props para Inertia.
     *
     * @param  string  $view  Nombre de la vista (por ejemplo: 'index', 'edit').
     * @param  Request  $request  Request HTTP actual.
     * @param  array<string, mixed>  $additionalData  Props adicionales específicas de la vista.
     * @param  array<int, array<string, mixed>>|null  $customPanelItems  Config de panel personalizada.
     * @param  array<int, array<string, mixed>>|null  $customNavItems  Config de nav contextual personalizada.
     * @param  string|null  $routeSuffix  Sufijo de ruta (si es null, se autodetecta).
     * @param  array<string, mixed>  $routeParams  Parámetros de ruta para resolver referencias.
     * @param  array<string, mixed>  $dynamicTitleData  Props para títulos dinámicos (breadcrumbs).
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException Si el usuario no está autenticado.
     */
    protected function prepareAndRenderModuleView(
        string $view,
        Request $request,
        array $additionalData = [],
        ?array $customPanelItems = null,
        ?array $customNavItems = null,
        ?string $routeSuffix = null,
        array $routeParams = [],
        array $dynamicTitleData = []
    ): InertiaResponse {
        $user = $request->user($this->getAuthGuard())
            ?: abort(403, 'Usuario no autenticado');

        if ($routeParams === []) {
            $route = $request->route();
            $routeParams = $route ? $route->parameters() : [];
        }

        $normalizedRouteParams = [];
        foreach ($routeParams as $key => $value) {
            $normalizedRouteParams[(string) $key] = $value;
        }

        $routeParams = $normalizedRouteParams;

        $panelItemsConfig = $customPanelItems
            ?? $this->getPanelItemsConfig();
        $contextualNavItemsConfig = $customNavItems
            ?? $this->getContextualNavItemsConfig();

        if ($this->navigationService instanceof MenuBuilderInterface) {
            $panelItemsConfig = $this->resolveConfigReferences(
                $panelItemsConfig,
                $routeParams
            );
            $contextualNavItemsConfig = $this->resolveConfigReferences(
                $contextualNavItemsConfig,
                $routeParams
            );
        }

        /** @var array<int, array<string, mixed>> $panelItemsConfig */
        $panelItemsConfig = is_array($panelItemsConfig)
            ? array_values(array_filter(
                $panelItemsConfig,
                is_array(...)
            ))
            : [];
        /** @var array<int, array<string, mixed>> $contextualNavItemsConfig */
        $contextualNavItemsConfig = is_array($contextualNavItemsConfig)
            ? array_values(array_filter(
                $contextualNavItemsConfig,
                is_array(...)
            ))
            : [];
        $functionalName = $this->getFunctionalName();

        $routeSuffix ??= $this->extractRouteSuffixFromRequest($request);

        $viewData = array_merge($additionalData, $dynamicTitleData);

        /** @var array<int, mixed>|null $statsParam */
        $statsParam = null;
        if (
            isset($additionalData['stats'])
            && is_array($additionalData['stats'])
        ) {
            $statsParam = array_values($additionalData['stats']);
        }

        $viewContext = $this->viewComposerService
            ->composeModuleViewContext(
                moduleSlug: $this->moduleSlug,
                panelItemsConfig: $panelItemsConfig,
                contextualNavItemsConfig: $contextualNavItemsConfig,
                permissionChecker: fn (string $permission): bool => (bool) $user->hasPermissionToCross($permission),
                user: $user,
                functionalName: $functionalName,
                data: $viewData,
                stats: $statsParam,
                routeSuffix: $routeSuffix,
                routeParams: $routeParams
            );

        return $this->renderModuleView($view, $viewContext);
    }

    /**
     * Detecta el slug del módulo desde el namespace del controlador hijo y carga su config.
     */
    private function detectModuleAndLoadConfig(): void
    {
        $namespace = new ReflectionClass(static::class)->getNamespaceName();

        $parts = explode('\\', $namespace);

        if ($parts[0] === 'Modules' && isset($parts[1])) {
            $this->moduleSlug = mb_strtolower($parts[1]);
            $this->moduleConfig = $this->moduleRegistryService
                ->getAddonConfig($this->moduleSlug);
        }
    }

    /**
     * Extrae el sufijo de la ruta para seleccionar navegación contextual por página.
     */
    private function extractRouteSuffixFromRequest(Request $request): string
    {
        $route = $request->route();
        $currentRoute = $route ? $route->getName() : null;

        if (
            $currentRoute && str_starts_with(
                $currentRoute,
                sprintf('internal.staff.%s.', $this->moduleSlug)
            )
        ) {
            return mb_substr(
                $currentRoute,
                mb_strlen(sprintf('internal.staff.%s.', $this->moduleSlug))
            );
        }

        if (
            $currentRoute && str_starts_with(
                $currentRoute,
                sprintf('internal.%s.', $this->moduleSlug)
            )
        ) {
            return mb_substr(
                $currentRoute,
                mb_strlen(sprintf('internal.%s.', $this->moduleSlug))
            );
        }

        $parts = explode('.', $currentRoute ?? '');

        return end($parts) ?: 'panel';
    }
}

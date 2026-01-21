<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Response as InertiaResponse;
use Modules\Core\Contracts\AddonRegistryInterface;
use Modules\Core\Contracts\MenuBuilderInterface;
use Modules\Core\Contracts\ModuleOrchestratorInterface;
use Modules\Core\Contracts\PermissionVerifierInterface;
use Modules\Core\Contracts\ViewComposerInterface;
use Modules\Core\Domain\User\StaffUser as StaffUserDomain;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser as StaffUserModel;
use Modules\Core\Infrastructure\Laravel\Mappers\StaffUserMapper;

/**
 * Servicio de orquestación de vistas de módulos (implementación Laravel).
 *
 * Delegado en contratos de Core; normaliza resolución de usuario,
 * composición de contexto y referencias declarativas. Añade consistencia
 * de rutas y permisos cross-guard sin acoplar a Infrastructure en módulos.
 */
final readonly class ModuleOrchestratorService implements ModuleOrchestratorInterface
{
    /**
     * @param  AddonRegistryInterface  $addonRegistry  Registro de módulos y sus configuraciones.
     * @param  ViewComposerInterface  $viewComposer  Servicio de composición y renderizado Inertia.
     * @param  PermissionVerifierInterface  $permissionVerifier  Verificador de permisos cross-guard.
     */
    public function __construct(
        private AddonRegistryInterface $addonRegistry,
        private ViewComposerInterface $viewComposer,
        private PermissionVerifierInterface $permissionVerifier
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function resolveAuthenticatedUser(
        Request $request,
        string $moduleSlug,
        ?array $moduleConfig = null
    ): ?Authenticatable {
        $config = $this->resolveModuleConfig($moduleSlug, $moduleConfig);

        $guard = $config['auth_guard'] ?? null;
        $guardName = is_string($guard) ? $guard : null;

        if ($guardName !== null && $guardName !== '') {
            $user = $request->user($guardName);

            return $user instanceof Authenticatable ? $user : null;
        }

        $guards = config('auth.guards', []);
        $guardsArr = is_array($guards) ? $guards : [];
        foreach (array_keys($guardsArr) as $candidate) {
            $candidateGuard = is_string($candidate) ? $candidate : (string) $candidate;
            if ($candidateGuard === '') {
                continue;
            }

            if (Auth::guard($candidateGuard)->check()) {
                $user = $request->user($candidateGuard);

                return $user instanceof Authenticatable ? $user : null;
            }
        }

        $defaultGuard = config('auth.defaults.guard');
        $defaultGuardName = is_string($defaultGuard) && $defaultGuard !== ''
            ? $defaultGuard
            : null;
        if ($defaultGuardName !== null) {
            $user = $request->user($defaultGuardName);

            return $user instanceof Authenticatable ? $user : null;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function renderModuleView(
        Request $request,
        string $moduleSlug,
        ?array $moduleConfig = null,
        array $additionalData = [],
        ?array $customPanelItems = null,
        ?array $customNavItems = null,
        ?string $routeSuffix = null,
        array $routeParams = [],
        array $dynamicTitleData = [],
        ?MenuBuilderInterface $navigationService = null,
        string $view = 'index'
    ): InertiaResponse {
        $config = $this->resolveModuleConfig($moduleSlug, $moduleConfig);

        $user = $this->resolveAuthenticatedUser($request, $moduleSlug, $config)
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

        $panelItemsConfig = $customPanelItems ?? ($config['panel_items'] ?? []);
        $contextualNavItemsConfig = $customNavItems
            ?? $this->resolveContextualNavConfig($config, $moduleSlug, $request);

        if ($navigationService instanceof MenuBuilderInterface) {
            $panelItemsConfig = $navigationService->resolveConfigReferences(
                $panelItemsConfig,
                $config
            );
            $contextualNavItemsConfig = $navigationService->resolveConfigReferences(
                $contextualNavItemsConfig,
                $config
            );
        }

        /** @var array<int, array<string, mixed>> $panelItemsConfig */
        $panelItemsConfig = is_array($panelItemsConfig)
            ? array_values(array_filter($panelItemsConfig, is_array(...)))
            : [];
        /** @var array<int, array<string, mixed>> $contextualNavItemsConfig */
        $contextualNavItemsConfig = is_array($contextualNavItemsConfig)
            ? array_values(array_filter($contextualNavItemsConfig, is_array(...)))
            : [];

        $functionalNameRaw = $config['functional_name'] ?? null;
        $functionalName = is_string($functionalNameRaw) ? $functionalNameRaw : null;

        $routeSuffix ??= $this->extractRouteSuffixFromRequest($request, $moduleSlug);

        $viewData = array_merge($additionalData, $dynamicTitleData);

        /** @var array<int, mixed>|null $statsParam */
        $statsParam = null;
        if (isset($additionalData['stats']) && is_array($additionalData['stats'])) {
            $statsParam = array_values($additionalData['stats']);
        }

        // PermissionChecker basado en entidad de dominio (permissions precalculados con caché cross-guard)
        $domainUser = null;
        if ($user instanceof StaffUserModel) {
            $domainUser = StaffUserMapper::toDomain($user);
        }

        $permissionChecker = function (string $permission) use ($domainUser, $user): bool {
            if (! $domainUser instanceof StaffUserDomain) {
                return $this->permissionVerifier->checkCrossGuard($user, $permission);
            }

            if ($domainUser->hasPermission($permission)) {
                return true;
            }

            return $this->permissionVerifier->checkCrossGuard($user, $permission);
        };

        $viewContext = $this->viewComposer->composeModuleViewContext(
            moduleSlug: $moduleSlug,
            panelItemsConfig: $panelItemsConfig,
            contextualNavItemsConfig: $contextualNavItemsConfig,
            permissionChecker: $permissionChecker,
            user: $user,
            functionalName: $functionalName,
            data: $viewData,
            stats: $statsParam,
            routeSuffix: $routeSuffix,
            routeParams: $routeParams
        );

        $inertiaDirRaw = $config['inertia_view_directory'] ?? null;
        $inertiaDir = is_string($inertiaDirRaw) && $inertiaDirRaw !== ''
            ? $inertiaDirRaw
            : $moduleSlug;

        return $this->viewComposer->renderModuleView(
            view: $view,
            moduleViewPath: $inertiaDir,
            data: $viewContext
        );
    }

    /**
     * @param  array<string, mixed>|null  $moduleConfig
     * @return array<string, mixed>
     */
    private function resolveModuleConfig(
        string $moduleSlug,
        ?array $moduleConfig
    ): array {
        if (is_array($moduleConfig) && $moduleConfig !== []) {
            return $this->normalizeModuleConfig($moduleConfig);
        }

        return $this->normalizeModuleConfig(
            $this->addonRegistry->getAddonConfig($moduleSlug)
        );
    }

    /**
     * @param  array<string, mixed>  $moduleConfig
     * @return array<int, mixed>
     */
    private function resolveContextualNavConfig(
        array $moduleConfig,
        string $moduleSlug,
        Request $request
    ): array {
        $navConfigAll = $moduleConfig['contextual_nav'] ?? [];
        if (! is_array($navConfigAll)) {
            return [];
        }

        $suffix = $this->extractRouteSuffixFromRequest($request, $moduleSlug);

        if (isset($navConfigAll[$suffix]) && is_array($navConfigAll[$suffix])) {
            return array_values($navConfigAll[$suffix]);
        }

        if (isset($navConfigAll['default']) && is_array($navConfigAll['default'])) {
            return array_values($navConfigAll['default']);
        }

        return [];
    }

    private function extractRouteSuffixFromRequest(
        Request $request,
        string $moduleSlug
    ): string {
        $route = $request->route();
        $currentRoute = $route ? $route->getName() : null;

        if (
            $currentRoute && str_starts_with(
                $currentRoute,
                sprintf('internal.staff.%s.', $moduleSlug)
            )
        ) {
            return mb_substr(
                $currentRoute,
                mb_strlen(sprintf('internal.staff.%s.', $moduleSlug))
            );
        }

        if (
            $currentRoute && str_starts_with(
                $currentRoute,
                sprintf('internal.%s.', $moduleSlug)
            )
        ) {
            return mb_substr(
                $currentRoute,
                mb_strlen(sprintf('internal.%s.', $moduleSlug))
            );
        }

        $parts = explode('.', $currentRoute ?? '');

        return end($parts) ?: 'panel';
    }

    /**
     * @param  array<mixed, mixed>  $raw
     * @return array<string, mixed>
     */
    private function normalizeModuleConfig(array $raw): array
    {
        $normalized = [];
        foreach ($raw as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        return $normalized;
    }
}

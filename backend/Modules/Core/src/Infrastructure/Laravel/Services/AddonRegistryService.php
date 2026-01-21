<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Contracts\AddonRegistryInterface;
use Modules\Core\Domain\Addon\AddonConfig;
use Modules\Core\Domain\Addon\AddonInstance;
use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser as User;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Laravel\Module as ModuleInstance;

/**
 * Servicio de registro y acceso a addons/módulos (implementación Laravel).
 *
 * Implementa lectura de configuraciones declarativas y estrategias
 * de caché/versionado para navegación y estado de módulos.
 */
final class AddonRegistryService implements AddonRegistryInterface
{
    /**
     * Cache de configuraciones de módulos para evitar lecturas repetidas.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $configCache = [];

    /**
     * {@inheritDoc}
     */
    public function getAvailableAddonsForUser(User $user): array
    {
        $this->syncModuleStatusesCache();

        // Usar collecciones para aprovechar funciones de orden superior
        return array_values(
            collect(Module::allEnabled())
                ->filter(fn ($module): bool => $module instanceof ModuleInstance
                    && $this->canUserAccessModule($user, $module))
                ->values()
                ->all()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessibleAddons(
        ?User $user = null
    ): array {
        $this->syncModuleStatusesCache();

        // Si no se proporciona usuario, intentar obtener el usuario autenticado desde cualquier guard
        if (! $user instanceof User) {
            $guards = config('auth.guards', []);
            $guardsArr = is_array($guards) ? $guards : [];
            foreach (array_keys($guardsArr) as $guardName) {
                $guard = is_string($guardName)
                    ? $guardName
                    : (string) $guardName;
                if (Auth::guard($guard)->check()) {
                    $user = Auth::guard($guard)->user();
                    break;
                }
            }
        }

        // Si hay un usuario autenticado, filtrar por sus permisos
        if ($user) {
            return $this->getAvailableAddonsForUser($user);
        }

        // Si no hay usuario, devolver todos los módulos habilitados
        return array_values(
            array_filter(
                Module::allEnabled(),
                fn ($m): bool => $m instanceof ModuleInstance
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAllEnabledAddons(): array
    {
        $this->syncModuleStatusesCache();

        return array_values(
            array_filter(
                Module::allEnabled(),
                fn ($m): bool => $m instanceof ModuleInstance
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAddonConfig(string $moduleName): array
    {
        $moduleSlug = mb_strtolower($moduleName);

        // Si ya tenemos la configuración en caché, devolverla
        if (isset($this->configCache[$moduleSlug])) {
            return $this->configCache[$moduleSlug];
        }

        // Obtener la configuración y guardarla en caché
        $configRaw = config($moduleSlug, []);
        $config = [];
        if (is_array($configRaw)) {
            foreach ($configRaw as $k => $v) {
                if (is_string($k)) {
                    $config[$k] = $v;
                }
            }
        }

        $this->configCache[$moduleSlug] = $config;

        /** @var array<string, mixed> $config */
        return $config;
    }

    /**
     * {@inheritDoc}
     */
    public function getAddonInstance(string $moduleName): ?AddonInstance
    {
        $config = $this->getAddonConfig($moduleName);
        if ($config === []) {
            return null;
        }

        $addonConfig = AddonConfig::fromArray($moduleName, $config);

        $guards = config('auth.guards', []);
        $guardsArr = is_array($guards) ? $guards : [];
        $availableGuards = array_values(array_filter(
            array_map(
                static fn (int|string $k): string => is_string($k) ? $k : (string) $k,
                array_keys($guardsArr)
            ),
            static fn (string $g): bool => $g !== ''
        ));

        if (! $addonConfig->isValidGuard($availableGuards)) {
            throw new InvalidAddonConfig(
                sprintf(
                    "El guard '%s' no está definido para el addon '%s'.",
                    (string) $addonConfig->authGuard,
                    $addonConfig->moduleSlug
                )
            );
        }

        return new AddonInstance(
            name: $moduleName,
            config: $addonConfig
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAllEnabledAddonInstances(): array
    {
        $this->syncModuleStatusesCache();

        $instances = [];

        foreach ($this->getAllEnabledAddons() as $module) {
            $name = $module->getName();
            $instance = $this->getAddonInstance($name);
            if ($instance instanceof AddonInstance) {
                $instances[] = $instance;
            }
        }

        return $instances;
    }

    /**
     * {@inheritDoc}
     *
     * Nota: Incrementa `core.nav_version` y limpia caché interna.
     */
    public function clearConfigCache(): void
    {
        $this->configCache = [];

        $cacheConfigRaw = config('core.cache', []);
        $cacheConfig = is_array($cacheConfigRaw) ? $cacheConfigRaw : [];
        $navVersionKey = is_string($cacheConfig['nav_version_key'] ?? null)
            ? $cacheConfig['nav_version_key']
            : 'core.nav_version';

        $rawVersion = Cache::get($navVersionKey, 0);
        $currentVersion = is_int($rawVersion)
            ? $rawVersion
            : (is_numeric($rawVersion)
                ? (int) $rawVersion
                : 0
            );
        Cache::forever($navVersionKey, $currentVersion + 1);
    }

    /**
     * {@inheritDoc}
     */
    public function getGlobalNavItems(?User $user = null): array
    {
        $cacheConfigRaw = config('core.cache', []);
        $cacheConfig = is_array($cacheConfigRaw) ? $cacheConfigRaw : [];
        $navCachePrefix = is_string($cacheConfig['nav_cache_prefix'] ?? null)
            ? $cacheConfig['nav_cache_prefix']
            : 'core:nav:';
        if (! str_ends_with($navCachePrefix, ':')) {
            $navCachePrefix .= ':';
        }

        $navVersionKey = is_string($cacheConfig['nav_version_key'] ?? null)
            ? $cacheConfig['nav_version_key']
            : 'core.nav_version';
        $ttlRaw = $cacheConfig['global_nav_items_ttl_seconds'] ?? 300;
        $ttlSeconds = is_int($ttlRaw)
            ? $ttlRaw
            : (is_numeric($ttlRaw)
                ? (int) $ttlRaw
                : 300
            );
        if ($ttlSeconds < 1) {
            $ttlSeconds = 300;
        }

        $keyParts = ['global_nav'];
        $rawNavVersion = Cache::get($navVersionKey, 0);
        $navVersion = is_int($rawNavVersion)
            ? $rawNavVersion
            : (is_numeric($rawNavVersion)
                ? (int) $rawNavVersion
                : 0
            );
        $keyParts[] = 'nv'.$navVersion;
        if ($user instanceof User) {
            $rawId = $user->getAuthIdentifier();
            $userId = is_string($rawId)
                ? $rawId
                : (is_int($rawId)
                    ? (string) $rawId
                    : 'guest'
                );
            $keyParts[] = $userId;
            $versionRaw = Cache::get('user.'.$userId.'.perm_version', 0);
            $version = is_int($versionRaw)
                ? $versionRaw
                : (is_numeric($versionRaw)
                    ? (int) $versionRaw
                    : 0
                );
            $keyParts[] = 'v'.$version;
            $permissions = $user->getAttribute('frontend_permissions');
            $keyParts[] = md5((string) json_encode($permissions));
        } else {
            $keyParts[] = 'guest';
        }

        $coreConfig = $this->getAddonConfig('core');
        $settingsGroup = [];
        if (
            isset($coreConfig['nav_components'])
            && is_array($coreConfig['nav_components'])
            && isset($coreConfig['nav_components']['groups'])
            && is_array($coreConfig['nav_components']['groups'])
            && isset($coreConfig['nav_components']['groups']['user_settings_nav'])
        ) {
            $settingsGroup = (array) $coreConfig['nav_components']['groups']['user_settings_nav'];
        }

        $keyParts[] = md5((string) json_encode($settingsGroup));

        $cacheKey = $navCachePrefix.'global:'.md5(implode('|', $keyParts));
        $cachedRaw = Cache::get($cacheKey);
        if (is_array($cachedRaw)) {
            $cachedItems = [];
            foreach ($cachedRaw as $v) {
                if (! is_array($v)) {
                    continue;
                }

                $title = is_string($v['title'] ?? null)
                    ? $v['title'] : '';
                $routeName = is_string($v['route_name'] ?? null)
                    ? $v['route_name'] : '';
                $icon = is_string($v['icon'] ?? null)
                    ? $v['icon'] : null;
                $permission = $v['permission'] ?? null;

                $cachedItems[] = [
                    'title' => $title,
                    'route_name' => $routeName,
                    'icon' => $icon,
                    'permission' => $permission,
                ];
            }

            if ($cachedItems !== []) {
                /** @var array<int, array<string, mixed>> $cachedItems */
                return $cachedItems;
            }
        }

        $items = [];
        foreach ($settingsGroup as $entry) {
            $value = null;
            if (is_string($entry) && str_starts_with($entry, '$ref:')) {
                $path = mb_substr($entry, 5);
                $parts = explode('.', $path);
                $cursor = $coreConfig;
                foreach ($parts as $part) {
                    if (! is_array($cursor) || ! array_key_exists($part, $cursor)) {
                        $cursor = null;
                        break;
                    }

                    $cursor = $cursor[$part];
                }

                if (is_array($cursor)) {
                    $value = $cursor;
                }
            } elseif (is_array($entry)) {
                $value = $entry;
            }

            if (! is_array($value)) {
                continue;
            }

            $title = is_string($value['title'] ?? null)
                ? $value['title'] : '';
            $routeName = is_string($value['route_name'] ?? null)
                ? $value['route_name'] : '';
            $icon = is_string($value['icon'] ?? null)
                ? $value['icon'] : null;
            $permission = $value['permission'] ?? null;

            if (
                $permission !== null
                && is_string($permission)
                && $user instanceof User
                && ! $user->hasPermissionToCross($permission)
            ) {
                continue;
            }

            $items[] = [
                'title' => $title,
                'route_name' => $routeName,
                'icon' => $icon,
                'permission' => $permission,
            ];
        }

        Cache::put($cacheKey, $items, now()->addSeconds($ttlSeconds));

        return $items;
    }

    /**
     * Sincroniza invalidación de navegación cuando cambia el estado de módulos.
     *
     * Detecta cambios en `modules_statuses.json` vía `filemtime` y, si cambia,
     * incrementa `core.nav_version` para invalidar navegación cacheada.
     */
    private function syncModuleStatusesCache(): void
    {
        $cacheConfigRaw = config('core.cache', []);
        $cacheConfig = is_array($cacheConfigRaw) ? $cacheConfigRaw : [];
        $mtimeKey = is_string($cacheConfig['modules_statuses_mtime_key'] ?? null)
            ? $cacheConfig['modules_statuses_mtime_key']
            : 'core.modules_statuses_mtime';

        $statusesFileRaw = config('modules.activators.file.statuses-file');
        $statusesFile = is_string($statusesFileRaw) && $statusesFileRaw !== ''
            ? $statusesFileRaw
            : base_path('modules_statuses.json');

        if ($statusesFile === '' || ! file_exists($statusesFile)) {
            return;
        }

        $mtimeRaw = filemtime($statusesFile);
        $mtime = is_int($mtimeRaw) ? $mtimeRaw : null;
        if ($mtime === null) {
            return;
        }

        $cachedMtimeRaw = Cache::get($mtimeKey);
        $cachedMtime = is_int($cachedMtimeRaw)
            ? $cachedMtimeRaw
            : (is_numeric($cachedMtimeRaw)
                ? (int) $cachedMtimeRaw
                : null
            );

        if ($cachedMtime === null || $cachedMtime !== $mtime) {
            $this->clearConfigCache();
            Cache::forever($mtimeKey, $mtime);
        }
    }

    /**
     * Determina si un usuario puede acceder a un módulo específico.
     */
    private function canUserAccessModule(
        User $user,
        ModuleInstance $module
    ): bool {
        $config = $this->getAddonConfig($module->getName());

        // Si no hay configuración, no permitir acceso
        if ($config === []) {
            return false;
        }

        $permission = $config['base_permission'] ?? null;
        $permissionStr = is_string($permission) ? $permission : null;
        $authGuardVal = $config['auth_guard'] ?? null;
        $authGuardStr = is_string($authGuardVal) ? $authGuardVal : null;

        // Si el guard del módulo no coincide con el del usuario, denegar acceso.
        if ($authGuardStr && $user->getAuthGuard() !== $authGuardStr) {
            return false;
        }

        // ADMIN y DEV (del guard 'staff') tienen acceso a todo.
        if (
            $user->getAuthGuard() === 'staff'
            && ($user->hasRole('ADMIN') || $user->hasRole('DEV'))
        ) {
            return true;
        }

        // Si no se requiere permiso, permitir acceso.
        if ($permissionStr === null) {
            return true;
        }

        // Preferir verificación entre guards usando método del contrato.
        return $user->hasPermissionToCross($permissionStr);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Core\Application\Menu;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\AddonRegistryInterface;
use Modules\Core\Domain\Menu\MenuConfigResolver;

/**
 * Construye breadcrumbs para rutas internas de módulos/addons.
 *
 * Soporta breadcrumbs declarativos por módulo (config) y fallback razonable.
 * Usa caché basado en `core.cache` y `core.nav_version` para invalidación global.
 */
final readonly class BuildBreadcrumbs
{
    /**
     * Constructor de la clase BuildBreadcrumbs.
     *
     * @param  AddonRegistryInterface  $moduleRegistry  Servicio de registro de módulos.
     * @param  MenuConfigResolver  $configResolver  Resolutor de configuración de navegación.
     */
    public function __construct(
        private AddonRegistryInterface $moduleRegistry,
        private MenuConfigResolver $configResolver
    ) {
        //
    }

    /**
     * Construye los breadcrumbs para un módulo y un sufijo de ruta.
     *
     * Ejemplo de uso:
     * - $breadcrumbs = $builder->execute('core', 'profile.edit');
     *
     * @param  string  $moduleSlug  Slug del módulo.
     * @param  string  $routeSuffix  Sufijo de la ruta.
     * @param  array<string, mixed>  $routeParams  Parámetros de la ruta.
     * @param  array<string, mixed>  $viewData  Datos de la vista.
     * @return array<int, array<string, mixed>> Lista de breadcrumbs.
     */
    public function execute(
        string $moduleSlug,
        string $routeSuffix,
        array $routeParams = [],
        array $viewData = []
    ): array {
        $t0 = microtime(true);
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
        $ttlRaw = $cacheConfig['breadcrumbs_ttl_seconds'] ?? 300;
        $ttlSeconds = is_int($ttlRaw)
            ? $ttlRaw
            : (is_numeric($ttlRaw)
                ? (int) $ttlRaw
                : 300
            );
        if ($ttlSeconds < 1) {
            $ttlSeconds = 300;
        }

        $moduleConfig = $this->moduleRegistry->getAddonConfig($moduleSlug);

        $rawNavVersion = Cache::get($navVersionKey, 0);
        $navVersion = is_int($rawNavVersion)
            ? $rawNavVersion
            : (is_numeric($rawNavVersion)
                ? (int) $rawNavVersion
                : 0
            );

        $key = implode('|', [
            'breadcrumbs',
            $moduleSlug,
            $routeSuffix,
            'nv'.$navVersion,
            md5((string) json_encode($routeParams)),
            md5((string) json_encode($moduleConfig)),
        ]);
        $cacheKey = $navCachePrefix.'breadcrumbs:'.md5($key);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            $out = [];
            foreach ($cached as $b) {
                if (! is_array($b)) {
                    continue;
                }

                $out[] = [
                    'title' => is_string($b['title'] ?? null)
                        ? $b['title']
                        : '',
                    'href' => is_string($b['href'] ?? null)
                        ? $b['href']
                        : '#',
                ];
            }

            if ($out !== []) {
                $this->logBuild($moduleSlug, $routeSuffix, count($out), true, $t0);

                return $out;
            }
        }

        /** @var array<string, mixed> $moduleConfigArr */
        $moduleConfigArr = $moduleConfig;

        // Verificar si existen breadcrumbs configurados para esta ruta
        if (
            ! isset($moduleConfigArr['breadcrumbs'])
            || ! is_array($moduleConfigArr['breadcrumbs'])
            || ! isset($moduleConfigArr['breadcrumbs'][$routeSuffix])
        ) {
            $fallback = $this->getFallbackBreadcrumb($moduleConfigArr, $moduleSlug);
            Cache::put($cacheKey, $fallback, now()->addSeconds($ttlSeconds));

            $this->logBuild($moduleSlug, $routeSuffix, count($fallback), false, $t0);

            return $fallback;
        }

        $breadcrumbsConfig = $moduleConfigArr['breadcrumbs'][$routeSuffix];
        $resolvedBreadcrumbsConfig = $this->configResolver->resolve(
            $breadcrumbsConfig,
            $moduleConfig,
            $routeParams
        );

        if (! is_array($resolvedBreadcrumbsConfig)) {
            $fallback = $this->getFallbackBreadcrumb($moduleConfigArr, $moduleSlug);
            Cache::put($cacheKey, $fallback, now()->addSeconds($ttlSeconds));

            $this->logBuild($moduleSlug, $routeSuffix, count($fallback), false, $t0);

            return $fallback;
        }

        $resolvedBreadcrumbsConfig = $this->flattenResolvedConfig($resolvedBreadcrumbsConfig);

        $breadcrumbs = [];

        foreach ($resolvedBreadcrumbsConfig as $config) {
            if (! is_array($config)) {
                continue;
            }

            $title = isset($config['title']) && is_string($config['title'])
                ? $config['title']
                : '';

            // Manejar títulos dinámicos
            $dynamicKey = isset($config['dynamic_title'])
                ? 'dynamic_title'
                : (isset($config['dynamic_title_prop'])
                    ? 'dynamic_title_prop'
                    : null
                );

            if (
                $dynamicKey
                && isset($config[$dynamicKey])
                && is_string($config[$dynamicKey])
                && $config[$dynamicKey] !== ''
            ) {
                $dynamicTitle = $this->extractDynamicTitle($config[$dynamicKey], $viewData);
                if ($dynamicTitle !== null) {
                    $title = $title.': '.$dynamicTitle;
                }
            }

            // Determinar href
            if (isset($config['href']) && is_string($config['href']) && $config['href'] !== '') {
                $href = $config['href'];
            } else {
                $routeName = isset($config['route_name']) && is_string($config['route_name'])
                    ? $config['route_name']
                    : null;

                if (in_array($routeName, [null, '', '0'], true)) {
                    $routeNameSuffix = isset($config['route_name_suffix']) && is_string($config['route_name_suffix'])
                        ? $config['route_name_suffix']
                        : null;
                    $routeName = in_array($routeNameSuffix, [null, '', '0'], true)
                        ? null
                        : sprintf('internal.staff.%s.%s', $moduleSlug, $routeNameSuffix);
                }

                $itemRouteParams = isset($config['route_params'])
                    ? (array) $config['route_params']
                    : (isset($config['route_parameters'])
                        ? (array) $config['route_parameters']
                        : []
                    );

                $itemRouteParams = $this->normalizeRouteParameters($itemRouteParams);

                if (
                    $routeName === null
                    && isset($config['route'])
                    && is_string($config['route'])
                    && $config['route'] !== ''
                ) {
                    $routeName = $config['route'];
                }

                $href = $routeName !== null
                    ? $this->generateRoute($routeName, $itemRouteParams)
                    : '#';
            }

            $breadcrumbs[] = [
                'title' => $title,
                'href' => $href,
            ];
        }

        Cache::put($cacheKey, $breadcrumbs, now()->addSeconds($ttlSeconds));

        $this->logBuild($moduleSlug, $routeSuffix, count($breadcrumbs), false, $t0);

        return $breadcrumbs;
    }

    /**
     * Construye breadcrumbs a partir de la configuración contextual (fallback).
     *
     * @param  array<int, array<string, mixed>>  $contextualItems  Ítems contextuales.
     * @param  string  $moduleSlug  Slug del módulo.
     * @param  string  $currentRoute  Ruta actual.
     * @return array<int, array<string, mixed>> Lista de breadcrumbs.
     */
    public function buildFromContextual(
        array $contextualItems,
        string $moduleSlug,
        string $currentRoute
    ): array {
        if (! str_starts_with($currentRoute, sprintf('internal.staff.%s.', $moduleSlug))) {
            $moduleConfig = $this->moduleRegistry->getAddonConfig($moduleSlug);

            return $this->getFallbackBreadcrumb(
                $moduleConfig,
                $moduleSlug
            );
        }

        // Intentar usar configuración explícita si existe
        $routeSuffix = mb_substr(
            $currentRoute,
            mb_strlen(sprintf('internal.staff.%s.', $moduleSlug))
        );
        $moduleConfig = $this->moduleRegistry->getAddonConfig($moduleSlug);

        if (
            isset($moduleConfig['breadcrumbs'])
            && is_array($moduleConfig['breadcrumbs'])
            && isset($moduleConfig['breadcrumbs'][$routeSuffix])
        ) {
            // Nota: Aquí no tenemos viewData ni routeParams fácilmente,
            // pero si se llama a este método es un fallback.
            return $this->execute($moduleSlug, $routeSuffix);
        }

        // Construir desde contextual
        $breadcrumbs = [];
        if ($contextualItems !== [] && isset($contextualItems[0])) {
            $firstItem = $contextualItems[0];
            $firstTitle = isset($firstItem['title']) && is_string($firstItem['title'])
                ? $firstItem['title']
                : ucfirst($moduleSlug);
            $firstHref = isset($firstItem['href']) && is_string($firstItem['href'])
                ? $firstItem['href']
                : '#';

            $breadcrumbs[] = ['title' => $firstTitle, 'href' => $firstHref];

            foreach ($contextualItems as $item) {
                $item = (array) $item;
                $isCurrent = isset($item['current']) && $item['current'] === true;
                $itemTitle = isset($item['title']) && is_string($item['title'])
                    ? $item['title']
                    : '';

                if ($isCurrent && ($firstTitle !== $itemTitle)) {
                    $breadcrumbs[] = [
                        'title' => $itemTitle,
                        'href' => (isset($item['href']) && is_string($item['href']))
                            ? $item['href']
                            : '#',
                    ];
                    break;
                }
            }
        }

        return $breadcrumbs;
    }

    /**
     * @param  array<mixed>  $resolvedConfig
     * @return array<mixed>
     */
    private function flattenResolvedConfig(array $resolvedConfig): array
    {
        $flattened = [];

        foreach ($resolvedConfig as $item) {
            if (is_array($item) && array_is_list($item)) {
                $allNestedArrays = true;
                foreach ($item as $nested) {
                    if (! is_array($nested)) {
                        $allNestedArrays = false;
                        break;
                    }
                }

                if ($allNestedArrays) {
                    foreach ($item as $nested) {
                        $flattened[] = $nested;
                    }

                    continue;
                }
            }

            $flattened[] = $item;
        }

        return $flattened;
    }

    /**
     * Obtiene una breadcrumb de respaldo.
     *
     * @param  array<string, mixed>  $config  Configuración del módulo.
     * @param  string  $slug  Slug del módulo.
     * @return array<int, array<string, mixed>> Lista con la breadcrumb de respaldo.
     */
    private function getFallbackBreadcrumb(array $config, string $slug): array
    {
        $routeName = null;
        if (isset($config['nav_item']) && is_array($config['nav_item'])) {
            $navItemRouteName = $config['nav_item']['route_name'] ?? null;
            $routeName = is_string($navItemRouteName) && $navItemRouteName !== ''
                ? $navItemRouteName
                : null;
        }

        $routeName ??= sprintf('internal.staff.%s.index', $slug);

        return [[
            'title' => (isset($config['functional_name']) && is_string($config['functional_name']))
                ? $config['functional_name']
                : ucfirst($slug),
            'href' => $this->generateRoute($routeName),
        ]];
    }

    /**
     * Extrae un título dinámico de los datos de la vista.
     *
     * @param  string  $path  Ruta de acceso a la propiedad en los datos (ej. 'model.name').
     * @param  array<string, mixed>  $data  Datos de la vista.
     * @return string|null Título extraído o null si no se encuentra.
     */
    private function extractDynamicTitle(string $path, array $data): ?string
    {
        $parts = explode('.', $path);
        $value = $data;

        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } elseif (is_object($value) && isset($value->$part)) {
                $value = $value->$part;
            } else {
                return null;
            }
        }

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * Normaliza los parámetros de la ruta para asegurar que sean un array asociativo válido.
     *
     * @param  array<mixed>  $params  Parámetros a normalizar.
     * @return array<string, mixed> Parámetros normalizados.
     */
    private function normalizeRouteParameters(array $params): array
    {
        $normalized = [];
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Genera una URL de ruta de forma segura.
     *
     * @param  string  $routeName  Nombre de la ruta.
     * @param  array<string, mixed>  $parameters  Parámetros de la ruta.
     * @return string URL generada o '#' si falla.
     */
    private function generateRoute(string $routeName, array $parameters = []): string
    {
        try {
            if (Route::has($routeName)) {
                return route($routeName, $parameters);
            }
        } catch (Exception) {
            // Fallo silencioso intencional
        }

        return '#';
    }

    /**
     * Registra métricas sobre la construcción de breadcrumbs.
     *
     * @param  string  $slug  Slug del módulo.
     * @param  string  $suffix  Sufijo de la ruta.
     * @param  int  $count  Cantidad de breadcrumbs generados.
     * @param  bool  $hit  Indica si hubo acierto en caché.
     * @param  float  $t0  Tiempo de inicio en microsegundos.
     */
    private function logBuild(
        string $slug,
        string $suffix,
        int $count,
        bool $hit,
        float $t0
    ): void {
        $durationMs = (microtime(true) - $t0) * 1000;
        Log::channel('domain_navigation')->info('breadcrumbs_build', [
            'module_slug' => $slug,
            'route_suffix' => $suffix,
            'count' => $count,
            'cache_hit' => $hit,
            'duration_ms' => $durationMs,
        ]);
    }
}

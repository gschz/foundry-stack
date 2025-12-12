<?php

declare(strict_types=1);

namespace Modules\Core\Application\Navigation;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\ModuleRegistryInterface;
use Modules\Core\Domain\Navigation\NavigationConfigResolver;

final readonly class BuildBreadcrumbs
{
    /**
     * Constructor de la clase BuildBreadcrumbs.
     *
     * @param  ModuleRegistryInterface  $moduleRegistry  Servicio de registro de módulos.
     * @param  NavigationConfigResolver  $configResolver  Resolutor de configuración de navegación.
     */
    public function __construct(
        private ModuleRegistryInterface $moduleRegistry,
        private NavigationConfigResolver $configResolver
    ) {
        //
    }

    /**
     * Ejecuta la construcción de breadcrumbs.
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
        $moduleConfig = $this->moduleRegistry->getModuleConfig($moduleSlug);

        $cacheKey = implode('|', [
            'breadcrumbs',
            $moduleSlug,
            $routeSuffix,
            md5((string) json_encode($routeParams)),
            md5((string) json_encode($moduleConfig)),
        ]);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            $out = [];
            foreach ($cached as $b) {
                if (! is_array($b)) {
                    continue;
                }

                $out[] = [
                    'title' => is_string($b['title'] ?? null) ? $b['title'] : '',
                    'href' => is_string($b['href'] ?? null) ? $b['href'] : '#',
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
            Cache::put($cacheKey, $fallback, 300);
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
            Cache::put($cacheKey, $fallback, 300);
            $this->logBuild($moduleSlug, $routeSuffix, count($fallback), false, $t0);

            return $fallback;
        }

        $breadcrumbs = [];

        foreach ($resolvedBreadcrumbsConfig as $config) {
            if (! is_array($config)) {
                continue;
            }

            $title = isset($config['title']) && is_string($config['title']) ? $config['title'] : '';

            // Manejar títulos dinámicos
            $dynamicKey = isset($config['dynamic_title'])
                ? 'dynamic_title'
                : (isset($config['dynamic_title_prop']) ? 'dynamic_title_prop' : null);

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
                    ? $config['route_name'] : null;

                if (in_array($routeName, [null, '', '0'], true)) {
                    $routeNameSuffix = isset($config['route_name_suffix']) && is_string($config['route_name_suffix'])
                        ? $config['route_name_suffix'] : null;
                    $routeName = in_array($routeNameSuffix, [null, '', '0'], true)
                        ? null
                        : sprintf('internal.%s.%s', $moduleSlug, $routeNameSuffix);
                }

                $itemRouteParams = isset($config['route_params'])
                    ? (array) $config['route_params']
                    : (isset($config['route_parameters']) ? (array) $config['route_parameters'] : []);

                $itemRouteParams = $this->normalizeRouteParameters($itemRouteParams);

                $href = $routeName !== null
                    ? $this->generateRoute($routeName, $itemRouteParams)
                    : '#';
            }

            $breadcrumbs[] = [
                'title' => $title,
                'href' => $href,
            ];
        }

        Cache::put($cacheKey, $breadcrumbs, 300);
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
        // Lógica simplificada de compatibilidad
        // Si no es una ruta del módulo, devolver solo el dashboard del módulo
        if (! str_starts_with($currentRoute, sprintf('internal.%s.', $moduleSlug))) {
            return [[
                'title' => ucfirst($moduleSlug),
                'href' => $this->generateRoute(sprintf('internal.%s.panel', $moduleSlug)),
            ]];
        }

        // Intentar usar configuración explícita si existe
        $routeSuffix = mb_substr($currentRoute, mb_strlen(sprintf('internal.%s.', $moduleSlug)));
        $moduleConfig = $this->moduleRegistry->getModuleConfig($moduleSlug);

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
                ? $firstItem['title'] : ucfirst($moduleSlug);
            $firstHref = isset($firstItem['href']) && is_string($firstItem['href'])
                ? $firstItem['href'] : '#';

            $breadcrumbs[] = ['title' => $firstTitle, 'href' => $firstHref];

            foreach ($contextualItems as $item) {
                $item = (array) $item;
                $isCurrent = isset($item['current']) && $item['current'] === true;
                $itemTitle = isset($item['title']) && is_string($item['title']) ? $item['title'] : '';

                if ($isCurrent && ($firstTitle !== $itemTitle)) {
                    $breadcrumbs[] = [
                        'title' => $itemTitle,
                        'href' => (isset($item['href']) && is_string($item['href'])) ? $item['href'] : '#',
                    ];
                    break;
                }
            }
        }

        return $breadcrumbs;
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
        return [[
            'title' => (isset($config['functional_name']) && is_string($config['functional_name']))
                ? $config['functional_name'] : ucfirst($slug),
            'href' => $this->generateRoute(sprintf('internal.%s.panel', $slug)),
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
    private function logBuild(string $slug, string $suffix, int $count, bool $hit, float $t0): void
    {
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

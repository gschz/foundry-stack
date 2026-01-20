<?php

declare(strict_types=1);

namespace Modules\Core\Application\Menu;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\AddonRegistryInterface;
use Modules\Core\Contracts\MenuBuilderInterface;
use Modules\Core\Domain\Menu\DTO\ContextualMenuItem;
use Modules\Core\Domain\Menu\DTO\PanelMenuItem;
use Modules\Core\Domain\Menu\MenuConfigResolver;
use Modules\Core\Infrastructure\Laravel\Events\MenuPermissionDenied;

/**
 * Construye navegación contextual y de panel para vistas de módulos.
 *
 * Resuelve referencias declarativas, valida DTOs de menú y registra
 * denegaciones/observabilidad. Soporta títulos dinámicos y rutas seguras.
 */
final readonly class BuildContextualMenu
{
    /**
     * @var array<string, array{
     *   textKey: string,
     *   textTemplateKey: string,
     *   extraFields: array<string, string>
     * }>
     */
    private const array NAV_TYPE_CONFIG = [
        MenuBuilderInterface::NAV_TYPE_CONTEXTUAL => [
            'textKey' => 'title',
            'textTemplateKey' => 'title_template',
            'extraFields' => [
                'href' => 'href',
                'current' => 'current',
            ],
        ],
        MenuBuilderInterface::NAV_TYPE_PANEL => [
            'textKey' => 'name',
            'textTemplateKey' => 'name_template',
            'extraFields' => [
                'route_name' => 'route_name',
                'description' => 'description',
            ],
        ],
    ];

    public function __construct(
        private AddonRegistryInterface $moduleRegistry,
        private MenuConfigResolver $configResolver
    ) {
        //
    }

    /**
     * Ejecuta la construcción de navegación contextual o de panel.
     *
     * @param  array<int, array<string, mixed>>  $itemsConfig
     * @return array<int, array<string, mixed>>
     */
    public function execute(
        string $navType,
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName = null
    ): array {
        // Verificar que el tipo de navegación es válido
        if (! isset(self::NAV_TYPE_CONFIG[$navType])) {
            Log::channel('domain_navigation')->warning('Tipo de navegación desconocido: '.$navType);

            return [];
        }

        // Resolver referencias en la configuración si existen
        $moduleConfig = $this->moduleRegistry->getAddonConfig($moduleSlug);

        $resolvedConfig = $this->configResolver->resolve(
            $itemsConfig,
            $moduleConfig
        );

        // Asegurar que la configuración resuelta sea un array secuencial de ítems
        $resolvedConfig = is_array($resolvedConfig)
            ? array_values($resolvedConfig)
            : [];
        /** @var array<int, array<mixed>> $resolvedConfig */
        $resolvedConfig = array_values(
            array_filter(
                $resolvedConfig,
                is_array(...)
            )
        );
        $resolvedConfig = $this->flattenResolvedConfig($resolvedConfig);
        $resolvedConfig = array_map(
            static function (array $item): array {
                $assoc = [];
                foreach ($item as $k => $v) {
                    $assoc[(string) $k] = $v;
                }

                return $assoc;
            },
            $resolvedConfig
        );

        // Obtener la configuración específica para el tipo de navegación
        $config = self::NAV_TYPE_CONFIG[$navType];

        /** @var array<int, array<string, mixed>> $resolvedConfig */
        return $this->buildItems(
            $resolvedConfig,
            $permissionChecker,
            $moduleSlug,
            $functionalName,
            $config['textKey'],
            $config['textTemplateKey'],
            $config['extraFields']
        );
    }

    /**
     * @param  array<int, array<mixed>>  $resolvedConfig
     * @return array<int, array<mixed>>
     */
    private function flattenResolvedConfig(array $resolvedConfig): array
    {
        $flattened = [];

        foreach ($resolvedConfig as $item) {
            if (array_is_list($item)) {
                $allNestedArrays = true;
                foreach ($item as $nested) {
                    if (! is_array($nested)) {
                        $allNestedArrays = false;
                        break;
                    }
                }

                if ($allNestedArrays) {
                    foreach ($item as $nested) {
                        $flattened[] = (array) $nested;
                    }

                    continue;
                }
            }

            $flattened[] = $item;
        }

        return $flattened;
    }

    /**
     * Construye los ítems individuales.
     *
     * @param  array<int, array<string, mixed>>  $itemsConfig
     * @param  array<string, string>  $extraFields
     * @return array<int, array<string, mixed>>
     */
    private function buildItems(
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName,
        string $textKey,
        string $textTemplateKey,
        array $extraFields
    ): array {
        $builtItems = [];

        foreach ($itemsConfig as $config) {
            // Validación previa según tipo de item
            $errors = [];
            if ($textKey === 'name') {
                $errors = PanelMenuItem::validate($config);
            } elseif ($textKey === 'title') {
                $errors = ContextualMenuItem::validate($config);
            }

            if ($errors !== []) {
                Log::channel('domain_navigation')->warning(
                    'Configuración de item inválida',
                    [
                        'module' => $moduleSlug,
                        'errors' => $errors,
                        'config' => $config,
                    ]
                );

                continue;
            }

            $permission = $config['permission'] ?? null;
            if ($permission) {
                $allowed = true;
                if (is_array($permission)) {
                    $allowed = array_any(
                        $permission,
                        fn ($perm): bool => is_string($perm) && $permissionChecker($perm)
                    );
                } elseif (is_string($permission)) {
                    $allowed = $permissionChecker($permission);
                }

                if (! $allowed) {
                    $this->recordNavPermissionDenial(
                        is_string($permission) ? $permission : null,
                        $moduleSlug
                    );

                    continue;
                }
            }

            // Determinar el texto a mostrar
            $text = isset($config[$textKey]) && is_string($config[$textKey])
                ? $config[$textKey]
                : null;
            if (
                isset($config[$textTemplateKey])
                && is_string($config[$textTemplateKey])
                && $functionalName
            ) {
                $text = sprintf($config[$textTemplateKey], $functionalName);
            }

            // Construir la ruta
            $routeName = $config['route_name'] ?? null;
            if (! $routeName) {
                $routeNameSuffix = $config['route_name_suffix'] ?? null;
                if ($routeNameSuffix && is_string($routeNameSuffix)) {
                    $routeName = sprintf('internal.staff.%s.%s', $moduleSlug, $routeNameSuffix);
                }
            }

            $href = '#';
            if ($routeName && is_string($routeName)) {
                $routeParams = $config['route_params'] ?? [];
                // Normalización simple
                $normalizedParams = [];
                if (is_array($routeParams)) {
                    /** @var string $k */
                    /** @var mixed $v */
                    foreach ($routeParams as $k => $v) {
                        $normalizedParams[$k] = $v;
                    }
                }

                $href = $this->generateRoute($routeName, $normalizedParams);
            } elseif (
                isset($config['href'])
                && is_string($config['href']) && $config['href'] !== ''
            ) {
                $href = $config['href'];
            } elseif (
                isset($config['route'])
                && is_string($config['route']) && $config['route'] !== ''
            ) {
                $paramsForRoute = $config['route_params'] ?? ($config['route_parameters'] ?? []);
                $normalizedParams = [];
                if (is_array($paramsForRoute)) {
                    foreach ($paramsForRoute as $k => $v) {
                        $normalizedParams[(string) $k] = $v;
                    }
                }

                $href = $this->generateRoute($config['route'], $normalizedParams);
            }

            $item = [
                $textKey => $text,
                'icon' => isset($config['icon']) && is_string($config['icon'])
                    ? $config['icon'] : null,
                'permission' => $permission,
            ];

            // Mapear campos extra
            if (isset($extraFields['href'])) {
                $item[$extraFields['href']] = $href;
            }

            if (isset($extraFields['route_name'])) {
                $item[$extraFields['route_name']] = $routeName;
            }

            if (isset($extraFields['description'])) {
                $item[$extraFields['description']] = $config['description'] ?? null;
            }

            if (isset($extraFields['current'])) {
                $current = false;
                if (is_string($routeName)) {
                    $current = Route::currentRouteName() === $routeName
                        || str_starts_with(Route::currentRouteName() ?? '', $routeName.'.');
                } elseif (
                    isset($config['current'])
                    && is_bool($config['current'])
                ) {
                    $current = $config['current'];
                }

                $item[$extraFields['current']] = $current;
            }

            $builtItems[] = $item;
        }

        return $builtItems;
    }

    /**
     * Genera la URL de una ruta de forma segura.
     *
     * @param  array<string, mixed>  $parameters
     */
    private function generateRoute(string $name, array $parameters = []): string
    {
        try {
            if (Route::has($name)) {
                return route($name, $parameters);
            }
        } catch (Exception) {
            // Ignorar errores de ruta no encontrada
        }

        return '#';
    }

    private function recordNavPermissionDenial(
        ?string $permission,
        ?string $moduleSlug = null
    ): void {
        if (! is_string($permission) || $permission === '') {
            return;
        }

        Cache::increment('metrics:navigation:denied:total');
        Cache::increment('metrics:navigation:denied:permission:'.$permission);
        if ($moduleSlug) {
            Cache::increment('metrics:navigation:denied:module:'.$moduleSlug);
        }

        event(new MenuPermissionDenied(
            permission: $permission,
            moduleSlug: $moduleSlug,
            user: null,
            context: 'contextual_nav'
        ));
    }
}

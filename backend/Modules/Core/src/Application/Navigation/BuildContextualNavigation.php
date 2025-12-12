<?php

declare(strict_types=1);

namespace Modules\Core\Application\Navigation;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\ModuleRegistryInterface;
use Modules\Core\Contracts\NavigationBuilderInterface;
use Modules\Core\Domain\Navigation\DTO\ContextualNavItem;
use Modules\Core\Domain\Navigation\DTO\PanelItem;
use Modules\Core\Domain\Navigation\NavigationConfigResolver;

final readonly class BuildContextualNavigation
{
    /**
     * @var array<string, array{
     *   textKey: string,
     *   textTemplateKey: string,
     *   extraFields: array<string, string>
     * }>
     */
    private const array NAV_TYPE_CONFIG = [
        NavigationBuilderInterface::NAV_TYPE_CONTEXTUAL => [
            'textKey' => 'title',
            'textTemplateKey' => 'title_template',
            'extraFields' => [
                'href' => 'route',
                'current' => 'current',
            ],
        ],
        NavigationBuilderInterface::NAV_TYPE_PANEL => [
            'textKey' => 'name',
            'textTemplateKey' => 'name_template',
            'extraFields' => [
                'route_name' => 'route_name',
                'description' => 'description',
            ],
        ],
    ];

    public function __construct(
        private ModuleRegistryInterface $moduleRegistry,
        private NavigationConfigResolver $configResolver
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
        $moduleConfig = $this->moduleRegistry->getModuleConfig($moduleSlug);

        $resolvedConfig = $this->configResolver->resolve(
            $itemsConfig,
            $moduleConfig
        );

        // Asegurar que la configuración resuelta sea un array secuencial de ítems
        $resolvedConfig = is_array($resolvedConfig)
            ? array_values($resolvedConfig)
            : [];
        /** @var array<int, array<string, mixed>> $resolvedConfig */
        $resolvedConfig = array_values(
            array_filter(
                $resolvedConfig,
                is_array(...)
            )
        );

        // Obtener la configuración específica para el tipo de navegación
        $config = self::NAV_TYPE_CONFIG[$navType];

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
                $errors = PanelItem::validate($config);
            } elseif ($textKey === 'title') {
                $errors = ContextualNavItem::validate($config);
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
                    $allowed = array_any($permission, fn ($perm): bool => is_string($perm) && $permissionChecker($perm));
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
                    $routeName = sprintf('internal.%s.%s', $moduleSlug, $routeNameSuffix);
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
                // Lógica simplificada para current
                $item[$extraFields['current']] = (
                    is_string($routeName)
                    && (
                        Route::currentRouteName() === $routeName
                        || str_starts_with(Route::currentRouteName() ?? '', $routeName.'.')
                    )
                );
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

        Log::channel('domain_navigation')->info('permission_denied', [
            'permission' => $permission,
            'module' => $moduleSlug,
            'context' => 'contextual_nav',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Core\Application\Menu;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * Construye navegación global (configuración) del panel.
 *
 * Genera ítems con href por ruta o URL directa y marca el ítem actual.
 * Registra denegaciones de permisos y normaliza parámetros de rutas.
 */
final class BuildGlobalMenu
{
    /**
     * Construye los ítems de navegación global.
     *
     * @param  array<int, array<string, mixed>>  $itemsConfig  Configuración de los ítems.
     * @param  callable  $permissionChecker  Función para verificar permisos.
     * @return array<int, array<string, mixed>> Lista de ítems de navegación global.
     */
    public function execute(
        array $itemsConfig,
        callable $permissionChecker
    ): array {
        $items = [];

        foreach ($itemsConfig as $config) {
            $permission = $config['permission'] ?? null;

            if ($permission && ! $permissionChecker($permission)) {
                $this->recordNavPermissionDenial(
                    is_string($permission) ? $permission : null
                );

                continue;
            }

            // Crear el ítem base
            $item = [
                'title' => isset($config['title']) && is_string($config['title'])
                    ? $config['title'] : '',
                'icon' => isset($config['icon']) && is_string($config['icon'])
                    ? $config['icon'] : null,
                'permission' => $permission,
            ];

            // Determinar la URL
            if (isset($config['href'])) {
                $href = is_string($config['href']) ? $config['href'] : '#';
                $item['href'] = $href;
                $item['current'] = $this->isCurrentUrl($href);
            } elseif (
                isset($config['route_name'])
                && is_string($config['route_name'])
            ) {
                $routeName = $config['route_name'];

                $routeParameters = (array) ($config['route_params']
                    ?? $config['route_parameters']
                    ?? []);

                // Normalización simple de parámetros
                $routeParameters = $this->normalizeRouteParameters($routeParameters);

                $item['href'] = route($routeName, $routeParameters);
                $item['current'] = $this->isCurrentRoute($routeName);
            } else {
                $item['href'] = '#';
                $item['current'] = false;
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Verifica si la URL dada corresponde a la URL actual.
     *
     * @param  string  $url  URL a verificar.
     * @return bool True si es la URL actual, false en caso contrario.
     */
    private function isCurrentUrl(string $url): bool
    {
        if (request()->fullUrlIs($url)) {
            return true;
        }

        return request()->is($url);
    }

    /**
     * Verifica si el nombre de ruta dado corresponde a la ruta actual.
     *
     * @param  string  $routeName  Nombre de la ruta a verificar.
     * @return bool True si es la ruta actual o una sub-ruta, false en caso contrario.
     */
    private function isCurrentRoute(string $routeName): bool
    {
        $currentRoute = Route::currentRouteName();

        if (! $currentRoute) {
            return false;
        }

        return $currentRoute === $routeName
            || str_starts_with($currentRoute, $routeName.'.');
    }

    /**
     * Normaliza los parámetros de ruta.
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
     * Registra la denegación de permiso para un ítem de navegación.
     *
     * @param  string|null  $permission  Permiso denegado.
     */
    private function recordNavPermissionDenial(
        ?string $permission
    ): void {
        if (! is_string($permission) || $permission === '') {
            return;
        }

        Cache::increment('metrics:navigation:denied:total');
        Cache::increment('metrics:navigation:denied:permission:'.$permission);

        Log::channel('domain_navigation')->info('permission_denied', [
            'permission' => $permission,
            'context' => 'global_nav',
        ]);
    }
}

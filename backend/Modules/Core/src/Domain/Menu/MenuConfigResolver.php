<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

/**
 * Resolver de configuración de menú declarativo.
 *
 * Soporta referencias tipo '$ref:path.to.component' dentro del config de módulo
 * y aplica parámetros de ruta cuando corresponde. Provee utilidades para
 * arrays asociativos/secuenciales y rutas alternativas (links/grupos).
 */
final class MenuConfigResolver
{
    /**
     * Resuelve referencias en la configuración del formato '$ref:path.to.component'.
     *
     * @param  mixed  $item  Configuración con posibles referencias
     * @param  array<string, mixed>  $config  Configuración completa del módulo
     * @param  array<string, mixed>  $routeParams  Parámetros adicionales para las rutas
     * @return mixed Configuración con referencias resueltas
     */
    public function resolve(
        mixed $item,
        array $config,
        array $routeParams = []
    ): mixed {
        // Si es un string y comienza con '$ref:', resolverlo
        if (is_string($item) && str_starts_with($item, '$ref:')) {
            $path = mb_substr($item, 5); // Remover '$ref:'
            $parts = explode('.', $path);
            $value = $config;

            // Intentar primero la ruta directa (validando tipos)
            $directPathFound = true;
            foreach ($parts as $part) {
                if (! is_array($value) || ! array_key_exists($part, $value)) {
                    $directPathFound = false;
                    break;
                }

                $value = $value[$part];
            }

            // Si la ruta directa no funciona, intentar buscar en ubicaciones alternativas
            if (! $directPathFound) {
                $value = $this->tryAlternativePaths($parts, $config, $directPathFound);
            }

            // Si ninguna ruta funcionó, devolver el valor original
            if (! $directPathFound) {
                return $item;
            }

            // Si encontramos un array, resolver referencias recursivamente
            if (is_array($value)) {
                return $this->resolve(
                    $value,
                    $config,
                    $routeParams
                );
            }

            // Mantener valor como referencia; generación de URL se delega a capas superiores

            return $value;
        }

        // Si es un array asociativo, verificar si tiene route_parameters y aplicar los parámetros de ruta
        if (
            is_array($item)
            && $this->isAssociativeArray($item)
            && isset($item['route_parameters'])
            && is_array($item['route_parameters'])
            && $routeParams !== []
        ) {
            /** @var array<string, mixed> $params */
            $params = $item['route_parameters'];
            $item['route_parameters'] = $this->processRouteParameters($params, $routeParams);
        }

        // Si es un array, resolver recursivamente cada elemento
        if (is_array($item)) {
            /** @var array<mixed> $itemArr */
            $itemArr = $item;

            return $this->isAssociativeArray($itemArr)
                ? $this->resolveAssociativeArray(
                    $itemArr,
                    $config,
                    $routeParams
                ) : $this->resolveSequentialArray(
                    array_values($itemArr),
                    $config,
                    $routeParams
                );
        }

        return $item;
    }

    /**
     * Intenta resolver rutas alternativas para referencias.
     *
     * @param  array<int, string>  $parts
     * @param  array<string, mixed>  $config
     */
    private function tryAlternativePaths(
        array $parts,
        array $config,
        bool &$found
    ): mixed {
        // Caso 1: Referencias a links (ej: $ref:links.panel)
        if (count($parts) >= 2 && $parts[0] === 'links') {
            $alternativePath = array_merge(
                ['nav_components', 'links', $parts[1]],
                count($parts) > 2 ? array_slice($parts, 2) : []
            );

            $result = $this->traversePath($alternativePath, $config, $found);
            if ($found) {
                return $result;
            }
        }

        // Caso 2: Referencias a grupos (ej: $ref:groups.user_management)
        if (count($parts) >= 2 && $parts[0] === 'groups') {
            $alternativePath = array_merge(
                ['nav_components', 'groups', $parts[1]],
                count($parts) > 2 ? array_slice($parts, 2) : []
            );

            $result = $this->traversePath($alternativePath, $config, $found);
            if ($found) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Recorre un path en la configuración.
     *
     * @param  array<int, string>  $path
     * @param  array<string, mixed>  $data
     */
    private function traversePath(
        array $path,
        array $data,
        bool &$found
    ): mixed {
        $value = $data;
        $found = true;

        foreach ($path as $part) {
            if (! is_array($value) || ! array_key_exists($part, $value)) {
                $found = false;

                return null;
            }

            $value = $value[$part];
        }

        return $value;
    }

    /**
     * Procesa parámetros de ruta reemplazando placeholders.
     *
     * @param  array<string, mixed>  $configParams
     * @param  array<string, mixed>  $runtimeParams
     * @return array<string, mixed>
     */
    private function processRouteParameters(
        array $configParams,
        array $runtimeParams
    ): array {
        $processed = [];
        foreach ($configParams as $key => $value) {
            if (is_string($value) && str_starts_with($value, ':')) {
                $paramName = mb_substr($value, 1);
                $processed[$key] = $runtimeParams[$paramName] ?? $value;
            } else {
                $processed[$key] = $value;
            }
        }

        return $processed;
    }

    /**
     * Resuelve un array asociativo.
     *
     * @param  array<mixed>  $item
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $routeParams
     * @return array<mixed>
     */
    private function resolveAssociativeArray(
        array $item,
        array $config,
        array $routeParams
    ): array {
        $resolved = [];
        foreach ($item as $key => $value) {
            $resolved[$key] = $this->resolve($value, $config, $routeParams);
        }

        return $resolved;
    }

    /**
     * Resuelve un array secuencial.
     *
     * @param  array<mixed>  $items
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $routeParams
     * @return array<mixed>
     */
    private function resolveSequentialArray(
        array $items,
        array $config,
        array $routeParams
    ): array {
        return array_map(
            fn ($item): mixed => $this->resolve($item, $config, $routeParams),
            $items
        );
    }

    /**
     * Determina si un array es asociativo.
     *
     * @param  array<mixed>  $array
     */
    private function isAssociativeArray(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return ! array_is_list($array);
    }
}

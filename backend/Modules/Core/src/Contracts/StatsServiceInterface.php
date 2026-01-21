<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Interfaz para servicios de estadísticas por módulo.
 *
 * Permite a cada módulo exponer un conjunto de estadísticas consumibles por el panel
 * sin acoplarse a implementaciones o DTOs específicos de la aplicación.
 */
interface StatsServiceInterface
{
    /**
     * Devuelve estadísticas del panel para un módulo y usuario dados.
     *
     * Ejemplo de uso:
     * - Implementaciones de módulos retornan una lista de objetos serializables (p. ej. DTOs) para Inertia.
     *
     * @param  string  $moduleSlug  Slug del módulo para contextualizar el cálculo.
     * @param  Authenticatable|null  $user  Usuario autenticado (si aplica).
     * @return array<int, mixed> Colección de estadísticas consumibles por el frontend.
     */
    public function getPanelStats(
        string $moduleSlug,
        ?Authenticatable $user = null
    ): array;
}

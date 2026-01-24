<?php

declare(strict_types=1);

namespace Modules\Module01\App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Core\Contracts\StatsServiceInterface;
use Modules\Core\Domain\Stats\EnhancedStat;

/**
 * Servicio de estadísticas del dashboard para Module01.
 *
 * Expone un conjunto de estadísticas consumibles por el panel del módulo.
 */
final class Module01StatsService implements StatsServiceInterface
{
    /**
     * {@inheritDoc}
     */
    public function getPanelStats(string $moduleSlug, ?Authenticatable $user = null): array
    {
        $panelItems = (array) config('module01.panel_items', []);
        $contextualDefault = (array) config('module01.contextual_nav.default', []);

        return [
            new EnhancedStat(
                key: 'panel_items',
                title: 'Ítems de panel',
                description: 'Total de accesos del panel',
                icon: 'layout-dashboard',
                value: count($panelItems),
            ),
            new EnhancedStat(
                key: 'contextual_links',
                title: 'Navegación contextual',
                description: 'Enlaces disponibles',
                icon: 'list',
                value: count($contextualDefault),
            ),
        ];
    }
}

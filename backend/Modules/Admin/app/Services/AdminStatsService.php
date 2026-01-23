<?php

declare(strict_types=1);

namespace Modules\Admin\App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Admin\App\Interfaces\StaffUserManagerInterface;
use Modules\Core\Contracts\StatsServiceInterface;
use Modules\Core\Domain\Stats\EnhancedStat;

/**
 * Servicio de estadísticas del dashboard para Admin.
 *
 * Expone estadísticas agregadas para el panel administrativo.
 */
final readonly class AdminStatsService implements StatsServiceInterface
{
    /**
     * @param  StaffUserManagerInterface  $staffUserManager  {@inheritDoc}
     */
    public function __construct(
        private StaffUserManagerInterface $staffUserManager,
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function getPanelStats(
        string $moduleSlug,
        ?Authenticatable $user = null
    ): array {
        $totalUsers = $this->staffUserManager->getTotalUsers();
        $totalRoles = $this->staffUserManager->getTotalRoles();

        return [
            new EnhancedStat(
                key: 'total_users',
                title: 'Usuarios',
                description: 'Usuarios del sistema',
                icon: 'users',
                value: $totalUsers,
            ),
            new EnhancedStat(
                key: 'total_roles',
                title: 'Roles',
                description: 'Roles disponibles',
                icon: 'shield-check',
                value: $totalRoles,
            ),
        ];
    }
}

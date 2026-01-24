<?php

declare(strict_types=1);

namespace Modules\Module02\App\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;
use Modules\Core\Application\Permissions\GetUserPermissions;
use Modules\Core\Domain\Stats\EnhancedStat;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Controlador principal del Módulo 02.
 *
 * Ejemplo Avanzado:
 * - Inyección explícita de configuración.
 * - Consumo directo de Casos de Uso del Core (GetUserPermissions).
 * - Manejo manual de contexto de vista adicional.
 */
final class Module02DashboardController extends AbstractModule02Controller
{
    /**
     * Renderiza el panel principal del módulo.
     *
     * @param  Request  $request  Request HTTP actual.
     * @param  GetUserPermissions  $getUserPermissions  Caso de uso para obtener permisos (ejemplo de consumo directo).
     * @return InertiaResponse Respuesta Inertia del panel.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException Si el usuario no está autenticado.
     */
    public function index(
        Request $request,
        GetUserPermissions $getUserPermissions
    ): InertiaResponse {
        $moduleConfigRaw = config('module02');
        /** @var array<string, mixed>|null $moduleConfig */
        $moduleConfig = is_array($moduleConfigRaw) ? $moduleConfigRaw : null;

        $user = $this->orchestrator->resolveAuthenticatedUser(
            $request,
            $this->getModuleSlug(),
            $moduleConfig
        );

        /** @var array<int, EnhancedStat> $stats */
        $stats = $this->statsService->getPanelStats(
            $this->getModuleSlug(),
            $user instanceof Authenticatable ? $user : null
        );

        // Ejemplo de consumo directo de Application Core
        $permissions = [];
        if ($user instanceof StaffUser) {
            $permissionsCollection = $getUserPermissions->handle($user);
            $permissions = $permissionsCollection->toArray();
        }

        return $this->orchestrator->renderModuleView(
            request: $request,
            moduleSlug: $this->getModuleSlug(),
            moduleConfig: $moduleConfig,
            additionalData: [
                'stats' => $stats,
                'debug_permissions' => $permissions, // Datos extra para demostración
            ],
            navigationService: $this->navigationBuilder,
            view: 'index'
        );
    }
}

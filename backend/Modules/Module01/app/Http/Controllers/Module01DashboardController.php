<?php

declare(strict_types=1);

namespace Modules\Module01\App\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;
use Modules\Core\Domain\Stats\EnhancedStat;

/**
 * Controlador principal del panel del Módulo 01.
 */
final class Module01DashboardController extends AbstractModule01Controller
{
    /**
     * Renderiza el panel principal del módulo.
     *
     * @param  Request  $request  Request HTTP actual.
     * @return InertiaResponse Respuesta Inertia del panel.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException Si el usuario no está autenticado.
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $this->orchestrator->resolveAuthenticatedUser(
            $request,
            $this->getModuleSlug()
        );

        /** @var array<int, EnhancedStat> $stats */
        $stats = $this->statsService->getPanelStats(
            $this->getModuleSlug(),
            $user instanceof Authenticatable ? $user : null
        );

        return $this->orchestrator->renderModuleView(
            request: $request,
            moduleSlug: $this->getModuleSlug(),
            additionalData: ['stats' => $stats],
            navigationService: $this->navigationBuilder,
            view: 'index'
        );
    }
}

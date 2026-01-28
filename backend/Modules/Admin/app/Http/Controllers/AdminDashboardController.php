<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request as IlluminateRequest;
use Inertia\Response as InertiaResponse;
use Modules\Admin\App\Interfaces\StaffUserManagerInterface;
use Modules\Core\Contracts\MenuBuilderInterface;
use Modules\Core\Contracts\ModuleOrchestratorInterface;
use Modules\Core\Contracts\StatsServiceInterface;
use Modules\Core\Domain\Stats\EnhancedStat;
use Spatie\Activitylog\Models\Activity;

/**
 * Controlador principal del panel de administración.
 *
 * Gestiona la visualización del dashboard administrativo y sus funcionalidades generales.
 */
final class AdminDashboardController extends AbstractAdminController
{
    /**
     * @param  StatsServiceInterface  $statsService  Servicio de estadísticas del panel del módulo.
     */
    public function __construct(
        ModuleOrchestratorInterface $moduleOrchestrator,
        MenuBuilderInterface $navigationBuilder,
        StaffUserManagerInterface $staffUserManager,
        private readonly StatsServiceInterface $statsService
    ) {
        parent::__construct(
            orchestrator: $moduleOrchestrator,
            navigationBuilder: $navigationBuilder,
            staffUserManager: $staffUserManager
        );
    }

    /**
     * Renderiza el panel principal del módulo.
     *
     * @param  IlluminateRequest  $request  Request HTTP actual.
     * @return InertiaResponse Respuesta Inertia del panel.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException Si el usuario no está autenticado.
     */
    public function index(IlluminateRequest $request): InertiaResponse
    {
        $user = $this->orchestrator->resolveAuthenticatedUser(
            $request,
            $this->getModuleSlug(),
        );

        /** @var array<int, EnhancedStat> $stats */
        $stats = $this->statsService->getPanelStats(
            $this->getModuleSlug(),
            $user instanceof Authenticatable ? $user : null
        );

        return $this->orchestrator->renderModuleView(
            request: $request,
            moduleSlug: $this->getModuleSlug(),
            additionalData: [
                'stats' => $stats,
                'recentActivity' => $this->getRecentActivity(),
            ],
            navigationService: $this->navigationBuilder,
            view: 'index'
        );
    }

    /**
     * Obtiene la actividad reciente para mostrar en el panel de administración.
     *
     * @return array<int, array{
     *     id: int,
     *     user: array{name: string},
     *     title: string,
     *     timestamp: string,
     *     icon: string
     * }>
     */
    private function getRecentActivity(): array
    {
        $activities = Activity::with('causer')->latest()->take(5)->get();

        return $activities->map(function (Activity $activity): array {
            /** @var \Illuminate\Database\Eloquent\Model|null $causer */
            $causer = $activity->causer;
            $causerName = 'Sistema';
            if ($causer !== null) {
                $attr = $causer->getAttribute('name');
                $causerName = is_string($attr) ? $attr : 'Sistema';
            }

            $created = $activity->created_at;
            $timestamp = $created instanceof \Carbon\Carbon
                ? $created->toIso8601String()
                : now()->toIso8601String();

            return [
                'id' => $activity->id,
                'user' => [
                    'name' => $causerName,
                ],
                'title' => $activity->description,
                'timestamp' => $timestamp,
                'icon' => $this->getIconForEvent($activity->event),
            ];
        })->all();
    }

    /**
     * Devuelve el nombre de ícono adecuado para el evento dado.
     *
     * @param  string|null  $event  Evento auditado (created, updated, deleted, etc.)
     * @return string Nombre del ícono según la semántica del evento
     */
    private function getIconForEvent(?string $event): string
    {
        $e = mb_strtolower((string) $event);

        return match ($e) {
            'created', 'create' => 'fileplus2',
            'updated', 'update' => 'pencil',
            'deleted', 'delete', 'removed', 'remove' => 'xcircle',
            'restored', 'restore' => 'checkcircle',
            'login', 'logged-in', 'logged_in', 'authenticated' => 'keyround',
            'logout', 'logged-out', 'logged_out' => 'lock',
            'role_assigned', 'role-granted', 'permission_assigned', 'permission-granted' => 'shieldcheck',
            'role_revoked', 'permission_revoked', 'permission-revoked' => 'shieldalert',
            default => 'activity',
        };
    }
}

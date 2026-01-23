<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers\StaffUsers;

use Illuminate\Http\Request as IlluminateRequest;
use Inertia\Response as InertiaResponse;
use Modules\Admin\App\Http\Controllers\AbstractAdminController;

/**
 * Controlador para la gestión de listado de usuarios del personal administrativo.
 */
final class ListStaffUsersController extends AbstractAdminController
{
    /**
     * Muestra la lista de usuarios.
     *
     * @param  IlluminateRequest  $request  Solicitud HTTP
     * @return InertiaResponse Respuesta Inertia con la lista de usuarios
     */
    public function index(IlluminateRequest $request): InertiaResponse
    {
        $params = [
            'search' => $request->input('search'),
            'role' => $request->input('role'),
            'sort_field' => $request->input('sort_field', 'created_at'),
            'sort_direction' => $request->input('sort_direction', 'desc'),
            'per_page' => is_numeric($request->input('per_page'))
                ? (int) $request->input('per_page')
                : 10,
        ];

        $additionalData = [
            'users' => $this->staffUserManager->getAllUsers($params),
            'roles' => $this->staffUserManager->getAllRoles(),
            'filters' => $request->only([
                'search',
                'role',
                'sort_field',
                'sort_direction',
            ]),
        ];

        return $this->orchestrator->renderModuleView(
            request: $request,
            moduleSlug: $this->getModuleSlug(),
            additionalData: $additionalData,
            navigationService: $this->navigationBuilder,
            view: 'user/list'
        );
    }
}

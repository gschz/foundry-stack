<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers\StaffUsers;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Support\Facades\Log;
use Inertia\Response as InertiaResponse;
use Modules\Admin\App\Http\Controllers\AbstractAdminController;
use Modules\Admin\App\Http\Controllers\StaffUsers\Concerns\NormalizesStaffUserPayload;
use Modules\Admin\App\Http\Requests\StaffUserRequest;

/**
 * Controlador para la creación de usuarios del personal administrativo.
 */
final class CreateStaffUserController extends AbstractAdminController
{
    use NormalizesStaffUserPayload;

    /**
     * Muestra el formulario de creación de un nuevo usuario.
     *
     * @param  IlluminateRequest  $request  Solicitud HTTP
     * @return InertiaResponse Respuesta Inertia con el formulario de creación
     */
    public function create(IlluminateRequest $request): InertiaResponse
    {
        $roles = $this->staffUserManager->getAllRoles();

        $additionalData = [
            'roles' => $roles,
        ];

        return $this->orchestrator->renderModuleView(
            request: $request,
            moduleSlug: $this->getModuleSlug(),
            additionalData: $additionalData,
            navigationService: $this->navigationBuilder,
            view: 'user/create'
        );
    }

    /**
     * Almacena un nuevo usuario.
     *
     * @param  StaffUserRequest  $request  Solicitud validada para creación de usuario
     * @return RedirectResponse|InertiaResponse Redirección o respuesta Inertia
     *
     * @throws \Illuminate\Validation\ValidationException Si la validación de entrada falla.
     */
    public function store(StaffUserRequest $request): RedirectResponse|InertiaResponse
    {
        $isInertiaRequest = (bool) $request->header('X-Inertia');

        if ($isInertiaRequest) {
            try {
                $validatedData = $this->buildCreatePayload($request);
                $user = $this->staffUserManager->createUser($validatedData);

                $nameAttr = $user->getAttribute('name');
                $userName = is_string($nameAttr) ? $nameAttr : '';
                session()->flash(
                    'success',
                    sprintf("Usuario '%s' creado exitosamente.", $userName)
                );

                $roles = $this->staffUserManager->getAllRoles();

                $additionalData = [
                    'roles' => $roles,
                    'user' => $user,
                    'preventRedirect' => true,
                ];

                return $this->orchestrator->renderModuleView(
                    request: $request,
                    moduleSlug: $this->getModuleSlug(),
                    additionalData: $additionalData,
                    navigationService: $this->navigationBuilder,
                    view: 'user/create'
                );
            } catch (Exception $exception) {
                Log::error(
                    'Error al crear usuario: '.$exception->getMessage(),
                    [
                        'data' => $request->except(['password', 'password_confirmation']),
                        'trace' => $exception->getTraceAsString(),
                    ]
                );

                session()->flash(
                    'error',
                    'Ocurrió un error al crear el usuario. Por favor, inténtalo nuevamente.'
                );

                $roles = $this->staffUserManager->getAllRoles();

                $additionalData = [
                    'roles' => $roles,
                    'errors' => [
                        'general' => 'Ocurrió un error al crear el usuario. Por favor, inténtalo nuevamente.',
                    ],
                ];

                return $this->orchestrator->renderModuleView(
                    request: $request,
                    moduleSlug: $this->getModuleSlug(),
                    additionalData: $additionalData,
                    navigationService: $this->navigationBuilder,
                    view: 'user/create'
                );
            }
        }

        try {
            $validatedData = $this->buildCreatePayload($request);
            $user = $this->staffUserManager->createUser($validatedData);

            $nameAttr = $user->getAttribute('name');
            $userName = is_string($nameAttr) ? $nameAttr : '';

            return to_route('internal.staff.admin.users.index')
                ->with(
                    'success',
                    sprintf("Usuario '%s' creado exitosamente.", $userName)
                );
        } catch (Exception $exception) {
            Log::error(
                'Error al crear usuario: '.$exception->getMessage(),
                [
                    'data' => $request->except(['password', 'password_confirmation']),
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            return back()
                ->withInput(
                    $request->except(['password', 'password_confirmation'])
                )
                ->with(
                    'error',
                    'Ocurrió un error al crear el usuario. Por favor, inténtalo nuevamente.'
                );
        }
    }
}

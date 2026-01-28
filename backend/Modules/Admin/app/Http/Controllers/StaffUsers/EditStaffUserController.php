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
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Controlador para la edición de usuarios del personal administrativo.
 */
final class EditStaffUserController extends AbstractAdminController
{
    use NormalizesStaffUserPayload;

    /**
     * Muestra el formulario de edición de un usuario existente.
     *
     * @param  IlluminateRequest  $request  Solicitud HTTP
     * @param  int  $id  ID del usuario a editar
     * @return InertiaResponse Respuesta Inertia con el formulario de edición
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException Si el usuario no existe o no está autenticado.
     */
    public function edit(IlluminateRequest $request, int $id): InertiaResponse
    {
        $user = $this->staffUserManager->getUserById($id);

        abort_unless($user instanceof StaffUser, 404, 'Usuario no encontrado');

        $roles = $this->staffUserManager->getAllRoles();

        $additionalData = [
            'user' => $user,
            'roles' => $roles,
        ];

        return $this->orchestrator->renderModuleView(
            request: $request,
            moduleSlug: $this->getModuleSlug(),
            additionalData: $additionalData,
            navigationService: $this->navigationBuilder,
            view: 'user/edit'
        );
    }

    /**
     * Actualiza un usuario existente.
     *
     * @param  StaffUserRequest  $request  Solicitud validada para actualización de usuario
     * @param  int  $id  ID del usuario a actualizar
     * @return RedirectResponse Redirección con mensaje de éxito
     *
     * @throws \Illuminate\Validation\ValidationException Si la validación de entrada falla.
     */
    public function update(StaffUserRequest $request, int $id): RedirectResponse
    {
        try {
            $user = $this->staffUserManager->getUserById($id);

            if (! ($user instanceof StaffUser)) {
                return to_route('internal.staff.admin.users.index')
                    ->with(
                        'error',
                        'Usuario no encontrado. No se pudo realizar la actualización.'
                    );
            }

            $validatedData = $this->buildUpdatePayload($request);

            $this->staffUserManager->updateUser($id, $validatedData);

            $filteredRoles = $this->normalizeRoleInputs($request);
            if ($filteredRoles !== []) {
                $this->staffUserManager->syncRoles($user, $filteredRoles);
            }

            $nameAttr = $user->getAttribute('name');
            $userName = is_string($nameAttr) ? $nameAttr : '';

            return to_route('internal.staff.admin.users.index')
                ->with(
                    'success',
                    sprintf("Usuario '%s' actualizado exitosamente.", $userName)
                );
        } catch (Exception $exception) {
            Log::error(
                'Error al actualizar usuario: '.$exception->getMessage(),
                [
                    'user_id' => $id,
                    'data' => $request->except([
                        'password',
                        'password_confirmation',
                    ]),
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            return back()
                ->withInput($request->except([
                    'password',
                    'password_confirmation',
                ]))
                ->with(
                    'error',
                    'Ocurrió un error al actualizar el usuario. Por favor, inténtalo nuevamente.'
                );
        }
    }

    /**
     * Elimina un usuario existente.
     *
     * @param  int  $id  ID del usuario a eliminar
     * @return RedirectResponse Redirección con mensaje de éxito o error
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $user = $this->staffUserManager->getUserById($id);

            if (! ($user instanceof StaffUser)) {
                return to_route('internal.staff.admin.users.index')
                    ->with(
                        'error',
                        'Usuario no encontrado. No se pudo realizar la eliminación.'
                    );
            }

            $hasProtectedRole = $user->roles
                ->pluck('name')
                ->contains(static fn ($name): bool => is_string($name)
                    && in_array(mb_strtoupper($name), ['ADMIN', 'DEV'], true));

            if ($hasProtectedRole) {
                return to_route('internal.staff.admin.users.index')
                    ->with(
                        'error',
                        'No se puede eliminar un usuario con roles protegidos (ADMIN o DEV).'
                    );
            }

            $deleted = $this->staffUserManager->deleteUser($id);

            if ($deleted) {
                $nameAttr = $user->getAttribute('name');
                $userName = is_string($nameAttr) ? $nameAttr : '';

                return to_route('internal.staff.admin.users.index')
                    ->with(
                        'success',
                        sprintf("Usuario '%s' eliminado exitosamente.", $userName)
                    );
            }

            return to_route('internal.staff.admin.users.index')
                ->with(
                    'error',
                    'No se pudo eliminar el usuario. Intente nuevamente.'
                );
        } catch (Exception $exception) {
            Log::error(
                'Error al eliminar usuario: '.$exception->getMessage(),
                [
                    'user_id' => $id,
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            return to_route('internal.staff.admin.users.index')
                ->with(
                    'error',
                    'Ocurrió un error al eliminar el usuario. Por favor, inténtalo nuevamente.'
                );
        }
    }
}

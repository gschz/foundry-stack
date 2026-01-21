<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Http\Controllers\Profile;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Application\Profile\UpdatePassword;

final class PasswordController extends AbstractProfileController
{
    /**
     * Muestra la página de configuración de la contraseña del usuario.
     */
    public function edit(): Response
    {
        $breadcrumbs = $this->buildBreadcrumbs('password.edit');

        return Inertia::render('profile/password', [
            'contextualNavItems' => $this->getProfileNavigationItems(),
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * Actualiza la contraseña del usuario.
     */
    public function update(
        UpdatePassword $updatePassword,
        Request $request
    ): RedirectResponse {
        /** @var array{current_password:string,password:string,password_confirmation?:string} $validated */
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        // Actualizar la contraseña y registrar fecha de cambio
        $user = $this->requireStaffUser($request);

        $updatePassword->handle($user, $validated['password']);

        return back();
    }
}

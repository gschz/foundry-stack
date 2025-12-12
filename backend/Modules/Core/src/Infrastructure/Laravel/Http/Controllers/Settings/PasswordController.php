<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Infrastructure\Laravel\Facades\Nav;

final class PasswordController extends BaseSettingsController
{
    /**
     * Muestra la página de configuración de la contraseña del usuario.
     */
    public function edit(): Response
    {
        $breadcrumbs = Nav::buildConfiguredBreadcrumbs(
            'core',
            'user.settings.password.edit'
        );

        return Inertia::render('settings/password', [
            'contextualNavItems' => $this->getSettingsNavigationItems(),
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * Actualiza la contraseña del usuario.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var array{current_password:string,password:string,password_confirmation?:string} $validated */
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        // Actualizar la contraseña y registrar fecha de cambio
        $user = $this->requireStaffUser($request);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
        ])->save();

        Log::channel('domain_settings')->info('Password actualizado', [
            'user_id' => $user->getAuthIdentifier(),
            'email' => $user->email,
            'changed_at' => now()->toISOString(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event('password_updated')
            ->withProperties([
                'changed_at' => now()->toISOString(),
            ])
            ->log('Actualización de contraseña en Settings');

        return back();
    }
}

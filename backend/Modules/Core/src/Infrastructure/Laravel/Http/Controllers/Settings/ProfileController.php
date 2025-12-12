<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Http\Controllers\Settings;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Infrastructure\Laravel\Facades\Nav;
use Modules\Core\Infrastructure\Laravel\Http\Requests\Settings\ProfileUpdateRequest;

final class ProfileController extends BaseSettingsController
{
    /**
     * Muestra la página de configuración del perfil del usuario.
     */
    public function edit(Request $request): Response
    {
        $this->requireStaffUser($request);

        $breadcrumbs = Nav::buildConfiguredBreadcrumbs(
            'core',
            'user.settings.profile.edit'
        );

        return Inertia::render('settings/profile', [
            // El modelo StaffUsers implementa MustVerifyEmail; siempre verdadero
            'mustVerifyEmail' => true,
            'status' => $request->session()->get('status'),
            'contextualNavItems' => $this->getSettingsNavigationItems(),
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * Actualiza la configuración del perfil del usuario.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $this->requireStaffUser($request);
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Log::channel('domain_settings')->info('Perfil actualizado', [
            'user_id' => $user->getAuthIdentifier(),
            'email' => $user->email,
            'changes' => $request->validated(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event('profile_updated')
            ->withProperties([
                'changes' => $request->validated(),
            ])
            ->log('Actualización de perfil en Settings');

        return to_route('internal.user.settings.profile.edit');
    }

    /**
     * Elimina la cuenta del usuario.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $this->requireStaffUser($request);

        Log::channel('domain_settings')->info('Perfil eliminado', [
            'user_id' => $user->getAuthIdentifier(),
            'email' => $user->email,
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event('profile_deleted')
            ->withProperties([
                'deleted_at' => now()->toISOString(),
            ])
            ->log('Eliminación de perfil en Settings');

        FacadesAuth::guard('staff')->logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

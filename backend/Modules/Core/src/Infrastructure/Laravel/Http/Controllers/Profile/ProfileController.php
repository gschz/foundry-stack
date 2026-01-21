<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Http\Controllers\Profile;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Application\Profile\UpdateProfile;
use Modules\Core\Infrastructure\Laravel\Http\Requests\Profile\ProfileUpdateRequest;

final class ProfileController extends AbstractProfileController
{
    /**
     * Muestra la página de configuración del perfil del usuario.
     */
    public function edit(Request $request): Response
    {
        $this->requireStaffUser($request);

        $breadcrumbs = $this->buildBreadcrumbs('profile.edit');

        return Inertia::render('profile/edit', [
            // El modelo StaffUsers implementa MustVerifyEmail; siempre verdadero
            'mustVerifyEmail' => true,
            'status' => $request->session()->get('status'),
            'contextualNavItems' => $this->getProfileNavigationItems(),
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * Actualiza la configuración del perfil del usuario.
     */
    public function update(
        UpdateProfile $updateProfile,
        ProfileUpdateRequest $request
    ): RedirectResponse {
        $user = $this->requireStaffUser($request);
        $updateProfile->handle($user, $request->validated());

        return to_route('internal.staff.profile.edit');
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

        Log::channel('domain_profile')->info('Perfil eliminado', [
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
            ->log('Eliminación perfil de usuario');

        FacadesAuth::guard('staff')->logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

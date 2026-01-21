<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Http\Controllers\Profile;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Contracts\AccountSecurity\ConfirmTwoFactorAuthInterface;
use Modules\Core\Contracts\AccountSecurity\DisableTwoFactorAuthInterface;
use Modules\Core\Contracts\AccountSecurity\RegenerateTwoFactorRecoveryCodesInterface;
use Modules\Core\Contracts\AccountSecurity\RevokeOtherSessionsInterface;
use Modules\Core\Contracts\AccountSecurity\SetupTwoFactorAuthInterface;

/**
 * Controlador de Seguridad de Cuenta del perfil (2FA, sesiones, dispositivos).
 *
 * Orquesta operaciones de seguridad mediante Contracts de Core:
 * - Configuración y confirmación de 2FA
 * - Deshabilitación de 2FA y regeneración de códigos
 * - Revocación de otras sesiones activas
 * Construye breadcrumbs y props para Inertia siguiendo la arquitectura hexagonal.
 */
final class AccountSecurityController extends AbstractProfileController
{
    /**
     * Muestra la vista de seguridad con estado de 2FA, sesiones y dispositivos.
     *
     * Obtiene usuario staff, breadcrumbs y calcula:
     * twoFactorRequired, twoFactorEnabled, twoFactorPending, currentSessionId,
     * sessionsCount y devices.
     */
    public function edit(Request $request): Response
    {
        $user = $this->requireStaffUser($request);

        $breadcrumbs = $this->buildBreadcrumbs('security.edit');

        $authIdentifier = $user->getAuthIdentifier();
        $userId = is_string($authIdentifier)
            ? $authIdentifier
            : (is_int($authIdentifier)
                ? (string) $authIdentifier
                : null
            );

        $twoFactorSecretEncrypted = $user->getAttribute('two_factor_secret');
        $twoFactorConfirmedAt = $user->getAttribute('two_factor_confirmed_at');

        $twoFactorRequired = (bool) config(
            'security.two_factor.staff.required',
            false
        );
        $twoFactorPending = is_string($twoFactorSecretEncrypted)
            && $twoFactorSecretEncrypted !== ''
            && $twoFactorConfirmedAt === null;
        $twoFactorEnabled = $twoFactorConfirmedAt !== null;

        $currentSessionId = $request->hasSession()
            ? $request->session()->getId()
            : null;

        $sessionsCount = 0;
        if ($userId !== null) {
            $count = DB::table('sessions')
                ->where('staff_user_id', $userId)
                ->count();
            $sessionsCount = (int) $count;
        }

        $devices = $user->loginInfos()
            ->latest('last_login_at')
            ->limit(10)
            ->get([
                'id',
                'ip_address',
                'device_type',
                'browser',
                'platform',
                'is_mobile',
                'is_trusted',
                'last_login_at',
                'login_count',
            ])
            ->map(static fn ($row): array => [
                'id' => $row->id,
                'ip_address' => $row->ip_address,
                'device_type' => $row->device_type,
                'browser' => $row->browser,
                'platform' => $row->platform,
                'is_mobile' => (bool) $row->is_mobile,
                'is_trusted' => (bool) $row->is_trusted,
                'last_login_at' => $row->last_login_at?->toISOString(),
                'login_count' => (int) $row->login_count,
            ])
            ->all();

        $twoFactorSetup = $request->session()->get('twoFactorSetup');
        $recoveryCodes = $request->session()->get('recoveryCodes');

        return Inertia::render('profile/security', [
            'contextualNavItems' => $this->getProfileNavigationItems(),
            'breadcrumbs' => $breadcrumbs,
            'security' => [
                'twoFactorRequired' => $twoFactorRequired,
                'twoFactorEnabled' => $twoFactorEnabled,
                'twoFactorPending' => $twoFactorPending,
                'currentSessionId' => is_string($currentSessionId)
                    ? $currentSessionId : null,
                'sessionsCount' => $sessionsCount,
                'devices' => $devices,
            ],
            'twoFactorSetup' => is_array($twoFactorSetup)
                ? $twoFactorSetup : null,
            'recoveryCodes' => is_array($recoveryCodes)
                ? $recoveryCodes : null,
        ]);
    }

    /**
     * Revoca sesiones activas distintas a la actual.
     */
    public function revokeOtherSessions(
        RevokeOtherSessionsInterface $revokeOtherSessions,
        Request $request
    ): RedirectResponse {
        $user = $this->requireStaffUser($request);
        $currentSessionId = $request->hasSession()
            ? $request->session()->getId() : null;

        $revoked = $revokeOtherSessions->handle(
            $user,
            is_string($currentSessionId)
                ? $currentSessionId : null
        );

        return back()->with(
            'success',
            $revoked > 0
                ? 'Sesiones revocadas correctamente.'
                : 'No había otras sesiones activas.'
        );
    }

    /**
     * Inicia configuración de 2FA (genera secreto y códigos de respaldo).
     *
     * Persiste resultado en la sesión para renderizado de la vista.
     */
    public function setupTwoFactor(
        SetupTwoFactorAuthInterface $setupTwoFactorAuth,
        Request $request
    ): RedirectResponse {
        $user = $this->requireStaffUser($request);
        $setup = $setupTwoFactorAuth->handle($user);

        return back()->with('twoFactorSetup', $setup);
    }

    /**
     * Confirma 2FA con código TOTP proporcionado por el usuario.
     */
    public function confirmTwoFactor(
        ConfirmTwoFactorAuthInterface $confirmTwoFactorAuth,
        Request $request
    ): RedirectResponse {
        $user = $this->requireStaffUser($request);
        /** @var array{code:string} $validated */
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $ok = $confirmTwoFactorAuth->handle($user, $validated['code']);

        return back()->with(
            $ok ? 'success' : 'error',
            $ok ? '2FA confirmado correctamente.' : 'Código inválido.'
        );
    }

    /**
     * Deshabilita 2FA para el usuario autenticado.
     */
    public function disableTwoFactor(
        DisableTwoFactorAuthInterface $disableTwoFactorAuth,
        Request $request
    ): RedirectResponse {
        $user = $this->requireStaffUser($request);
        $disableTwoFactorAuth->handle($user);

        return back()->with('success', '2FA deshabilitado correctamente.');
    }

    /**
     * Regenera códigos de recuperación de 2FA.
     *
     * Requiere 2FA previamente confirmado.
     */
    public function regenerateRecoveryCodes(
        RegenerateTwoFactorRecoveryCodesInterface $regenerateTwoFactorRecoveryCodes,
        Request $request
    ): RedirectResponse {
        $user = $this->requireStaffUser($request);

        $confirmedAt = $user->getAttribute('two_factor_confirmed_at');
        if ($confirmedAt === null) {
            return back()->with(
                'error',
                'Debes confirmar 2FA antes de regenerar códigos.'
            );
        }

        $codes = $regenerateTwoFactorRecoveryCodes->handle($user);

        return back()->with('recoveryCodes', $codes);
    }
}

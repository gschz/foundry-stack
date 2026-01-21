<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;
use Modules\Core\Contracts\AccountSecurity\SecurityAuditInterface;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;
use Modules\Core\Infrastructure\Laravel\Notifications\AccountLoginNotification;
use Throwable;

/**
 * Servicio de auditoría y seguridad de sesión para usuarios internos.
 *
 * Normaliza operaciones de preparación de sesión, notificación de inicios
 * de sesión sospechosos y cierre de sesión. Registra eventos relevantes en
 * el canal de seguridad unificado.
 */
final readonly class SecurityAuditService implements SecurityAuditInterface
{
    public function __construct(private Agent $agent)
    {
        //
    }

    /**
     * Prepara la sesión después de una autenticación exitosa.
     * Regenera la sesión y el token para prevenir ataques de fijación de sesión.
     *
     * @param  Request  $request  Solicitud HTTP actual.
     */
    public function prepareAuthenticatedSession(Request $request): void
    {
        if ($request->hasSession()) {
            $request->session()->regenerate();
            $request->session()->save();
        }
    }

    /**
     * Maneja la notificación de inicio de sesión para inicios de sesión sospechosos.
     * Por el momento solo se aplica a los usuarios de tipo StaffUsers.
     *
     * @param  Authenticatable  $user  Usuario autenticado.
     * @param  Request  $request  Solicitud HTTP actual.
     *
     * @throws Throwable En caso de errores inesperados durante la notificación.
     */
    public function handleSuspiciousLoginNotification(
        Authenticatable $user,
        Request $request
    ): void {
        if (! $user instanceof StaffUser) {
            return;
        }

        try {
            $ipAddress = $request->ip() ?? 'Desconocida';
            $userAgent = $request->userAgent();

            if ($userAgent) {
                $this->agent->setUserAgent($userAgent);
            }

            $deviceInfo = [
                'device' => $this->agent->device() ?: 'Desconocido',
                'platform' => $this->agent->platform() ?: 'Desconocido',
                'browser' => $this->agent->browser() ?: 'Desconocido',
                'is_mobile' => $this->agent->isMobile(),
            ];

            // Primero, registra siempre el intento de login.
            $loginInfo = $user->recordLogin(
                $ipAddress,
                $userAgent,
                $deviceInfo
            );

            $deviceDescription = mb_trim(
                $deviceInfo['platform'].' '.$deviceInfo['browser']
            );
            $deviceDescription = $deviceDescription !== '' && $deviceDescription !== '0'
                ? $deviceDescription : 'Dispositivo desconocido';

            // Luego, si el login es sospechoso, envía la notificación.
            if ($user->isSuspiciousLogin($ipAddress, $userAgent)) {
                $user->notify(
                    new AccountLoginNotification(
                        $ipAddress,
                        $deviceDescription,
                        'Ubicación desconocida',
                        $loginInfo->id
                    )
                );
            }
        } catch (Exception $exception) {
            // Loguea el error con más contexto para facilitar la depuración.
            $id = $user->getAuthIdentifier();
            $uid = is_string($id)
                ? $id
                : (is_int($id)
                    ? (string) $id
                    : 'desconocido'
                );

            Log::channel('security_core')->warning(
                'Error al procesar notificación de login para el usuario: '.$uid,
                [
                    'error' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                ]
            );
        }
    }

    /**
     * Cierra la sesión del usuario para un guard específico.
     *
     * @param  Request  $request  Solicitud HTTP actual.
     * @param  string  $guard  Nombre del guard.
     */
    public function logout(Request $request, string $guard): void
    {
        Auth::guard($guard)->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Asegurarse que la variable de sesión auth se limpia
            $request->session()->forget(['auth', 'auth.password_confirmed_at']);

            // Forzar que la sesión se guarde inmediatamente
            $request->session()->save();
        }

        // Limpiar las cookies relacionadas con la sesión
        $cookieName = config('session.cookie');
        if (is_string($cookieName)) {
            $request->cookies->remove($cookieName);

            // Si estamos en modo debug, loguear información
            if (config('app.debug')) {
                Log::info(
                    'Sesión cerrada para guard: '.$guard,
                    [
                        'cookie_name' => $cookieName,
                        'session_id' => $request->session()->getId(),
                        'cookie_removed' => true,
                    ]
                );
            }
        }
    }
}

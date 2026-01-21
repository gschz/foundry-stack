<?php

declare(strict_types=1);

namespace Modules\Core\Application\AccountSecurity;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Core\Contracts\AccountSecurity\RevokeOtherSessionsInterface;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Caso de uso: revocar sesiones activas del usuario (excepto la actual).
 *
 * Elimina filas en la tabla de sesiones; registra auditoría y actividad.
 */
final readonly class RevokeOtherSessions implements RevokeOtherSessionsInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(StaffUser $user, ?string $currentSessionId): int
    {
        $query = DB::table('sessions')
            ->where('staff_user_id', $user->getAuthIdentifier());

        if (is_string($currentSessionId) && $currentSessionId !== '') {
            $query->where('id', '!=', $currentSessionId);
        }

        $revoked = (int) $query->delete();

        Log::channel('domain_audit')->info('Sesiones revocadas', [
            'user_id' => $user->getAuthIdentifier(),
            'revoked_count' => $revoked,
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event('sessions_revoked')
            ->withProperties([
                'revoked_count' => $revoked,
            ])
            ->log('Revocación de sesiones activas');

        return $revoked;
    }
}

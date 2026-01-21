<?php

declare(strict_types=1);

namespace Modules\Core\Application\AccountSecurity;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Modules\Core\Contracts\AccountSecurity\RegenerateTwoFactorRecoveryCodesInterface;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Caso de uso: regenerar códigos de recuperación de 2FA.
 *
 * Genera nuevos códigos, los cifra y registra auditoría/actividad.
 */
final readonly class RegenerateTwoFactorRecoveryCodes implements RegenerateTwoFactorRecoveryCodesInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(StaffUser $user): array
    {
        $rawCount = config('security.two_factor.staff.backup_codes_count', 10);
        $count = is_int($rawCount)
            ? $rawCount
            : (is_numeric($rawCount)
                ? (int) $rawCount
                : 10
            );
        $recoveryCodes = $this->generateRecoveryCodes($count);

        $user->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(
                (string) json_encode(array_map(
                    static fn (string $code): string => Hash::make($code),
                    $recoveryCodes,
                ))
            ),
        ])->save();

        Log::channel('domain_audit')->info('2FA recovery codes regenerados', [
            'user_id' => $user->getAuthIdentifier(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event('two_factor_recovery_codes_regenerated')
            ->withProperties([
                'count' => count($recoveryCodes),
            ])
            ->log('Regeneración de códigos de recuperación de 2FA');

        return $recoveryCodes;
    }

    /**
     * @return list<string>
     */
    private function generateRecoveryCodes(int $count): array
    {
        $count = $count > 0 ? $count : 10;
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = mb_strtoupper(bin2hex(random_bytes(5)));
        }

        return $codes;
    }
}

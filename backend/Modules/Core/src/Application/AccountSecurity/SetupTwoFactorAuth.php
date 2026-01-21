<?php

declare(strict_types=1);

namespace Modules\Core\Application\AccountSecurity;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Modules\Core\Contracts\AccountSecurity\SetupTwoFactorAuthInterface;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Caso de uso: iniciar configuración de 2FA (TOTP) para un usuario.
 *
 * Genera secreto base32, URI de aprovisionamiento y códigos de recuperación;
 * persiste cifrado y registra auditoría/actividad.
 */
final readonly class SetupTwoFactorAuth implements SetupTwoFactorAuthInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(StaffUser $user): array
    {
        $secret = $this->generateBase32Secret(20);
        $rawCount = config('security.two_factor.staff.backup_codes_count', 10);
        $count = is_int($rawCount)
            ? $rawCount
            : (is_numeric($rawCount)
                ? (int) $rawCount
                : 10
            );
        $recoveryCodes = $this->generateRecoveryCodes($count);

        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => Crypt::encryptString(
                (string) json_encode(array_map(
                    static fn (string $code): string => Hash::make($code),
                    $recoveryCodes,
                ))
            ),
            'two_factor_confirmed_at' => null,
        ])->save();

        Log::channel('domain_audit')->info('2FA setup iniciado', [
            'user_id' => $user->getAuthIdentifier(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event('two_factor_setup_started')
            ->log('Inicio de configuración de 2FA');

        $rawIssuer = config('app.name', 'Foundry Stack');
        $issuer = is_string($rawIssuer) && $rawIssuer !== ''
            ? $rawIssuer
            : 'Foundry Stack';

        return [
            'secret' => $secret,
            'provisioning_uri' => $this->buildProvisioningUri(
                secret: $secret,
                userEmail: $user->email,
                issuer: $issuer
            ),
            'recovery_codes' => $recoveryCodes,
        ];
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

    private function buildProvisioningUri(
        string $secret,
        string $userEmail,
        string $issuer
    ): string {
        $label = rawurlencode($issuer.':'.$userEmail);
        $issuerParam = rawurlencode($issuer);

        return 'otpauth://totp/'.$label
            .'?secret='.rawurlencode($secret)
            .'&issuer='.$issuerParam
            .'&algorithm=SHA1&digits=6&period=30';
    }

    private function generateBase32Secret(int $byteLength): string
    {
        $raw = random_bytes($byteLength > 0 ? $byteLength : 20);

        return $this->base32Encode($raw);
    }

    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        $length = mb_strlen($data, '8bit');

        for ($i = 0; $i < $length; $i++) {
            $binary .= mb_str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }

        $chunks = mb_str_split($binary, 5);
        $encoded = '';

        foreach ($chunks as $chunk) {
            if (mb_strlen($chunk) < 5) {
                $chunk = mb_str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }

            $index = (int) bindec($chunk);
            $encoded .= $alphabet[$index];
        }

        return $encoded;
    }
}

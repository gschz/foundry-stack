<?php

declare(strict_types=1);

namespace Modules\Core\Application\AccountSecurity;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Modules\Core\Contracts\AccountSecurity\ConfirmTwoFactorAuthInterface;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * Caso de uso: confirmar el código de 2FA (TOTP) del usuario.
 *
 * Verifica el código TOTP usando el secreto almacenado y marca 2FA como
 * confirmado; registra auditoría y actividad.
 */
final readonly class ConfirmTwoFactorAuth implements ConfirmTwoFactorAuthInterface
{
    /**
     * {@inheritDoc}
     *
     * Nota: Registra eventos en canal de auditoría y en activitylog.
     */
    public function handle(StaffUser $user, string $code): bool
    {
        $secretEncrypted = $user->getAttribute('two_factor_secret');
        if (! is_string($secretEncrypted) || $secretEncrypted === '') {
            return false;
        }

        $secret = Crypt::decryptString($secretEncrypted);
        if (! $this->verifyTotp($secret, $code)) {
            return false;
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        Log::channel('domain_audit')->info('2FA confirmado', [
            'user_id' => $user->getAuthIdentifier(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event('two_factor_confirmed')
            ->log('Confirmación de 2FA');

        return true;
    }

    private function verifyTotp(string $base32Secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $secret = $this->base32Decode(mb_strtoupper($base32Secret));
        if ($secret === '') {
            return false;
        }

        $step = 30;
        $windowSecondsRaw = config('security.two_factor.staff.totp_window', 30);
        $windowSeconds = is_int($windowSecondsRaw)
            ? $windowSecondsRaw
            : (is_numeric($windowSecondsRaw)
                ? (int) $windowSecondsRaw
                : 30
            );
        $windowSteps = max(0, (int) floor($windowSeconds / $step));

        $now = Date::now()->getTimestamp();
        $counter = (int) floor($now / $step);

        for ($i = -$windowSteps; $i <= $windowSteps; $i++) {
            $expected = $this->totpAtCounter($secret, $counter + $i);
            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    private function totpAtCounter(string $secret, int $counter): string
    {
        $counter = max($counter, 0);
        $binCounter = pack('N*', 0, $counter);
        $hash = hash_hmac('sha1', $binCounter, $secret, true);
        $offset = ord(mb_substr($hash, -1)) & 0x0F;
        $segment = mb_substr($hash, $offset, 4);
        $value = unpack('N', $segment);
        $unpacked = 0;
        if (is_array($value)) {
            $raw = $value[1] ?? 0;
            $unpacked = is_int($raw)
                ? $raw
                : (is_numeric($raw)
                    ? (int) $raw
                    : 0
                );
        }

        $int = $unpacked & 0x7FFFFFFF;
        $otp = $int % 1000000;

        return mb_str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $map = array_flip(mb_str_split($alphabet));
        $data = mb_rtrim($data, '=');
        $data = preg_replace('/[^A-Z2-7]/', '', $data) ?? '';

        $binary = '';
        $length = mb_strlen($data);

        for ($i = 0; $i < $length; $i++) {
            $char = $data[$i];
            if (! isset($map[$char])) {
                return '';
            }

            $binary .= mb_str_pad(
                decbin((int) $map[$char]),
                5,
                '0',
                STR_PAD_LEFT
            );
        }

        $bytes = '';
        foreach (mb_str_split($binary, 8) as $byteChunk) {
            if (mb_strlen($byteChunk) < 8) {
                continue;
            }

            $bytes .= chr((int) bindec($byteChunk));
        }

        return $bytes;
    }
}

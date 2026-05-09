<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación para el restablecimiento de contraseña.
 */
final class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * El callback que se debe usar para construir el mensaje de correo.
     *
     * @var (callable(CanResetPassword, string): MailMessage)|null
     */
    public static $toMailCallback;

    /**
     * Crea una nueva instancia de la notificación.
     *
     * @param  string  $token  El token de restablecimiento de contraseña.
     */
    public function __construct(public string $token)
    {
        //
    }

    /**
     * Define un callback para personalizar la construcción del mensaje de correo.
     *
     * @param  callable(CanResetPassword, string): MailMessage  $callback
     */
    public static function toMailUsing(mixed $callback): void
    {
        self::$toMailCallback = $callback;
    }

    /**
     * Obtiene los canales de entrega de la notificación.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Construye la representación por correo electrónico de la notificación.
     *
     * @param  Model&Authenticatable&CanResetPassword  $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Si se ha definido un callback personalizado, se utiliza para construir el mensaje.
        if (self::$toMailCallback) {
            return call_user_func(
                self::$toMailCallback,
                $notifiable,
                $this->token
            );
        }

        // Obtiene el tiempo de expiración del token desde la configuración.
        $defaultBroker = config('auth.defaults.passwords');
        $broker = is_string($defaultBroker) ? $defaultBroker : 'users';
        $expireRaw = config('auth.passwords.'.$broker.'.expire');
        $expirationInMinutes = is_numeric($expireRaw) ? (int) $expireRaw : 60;

        // Construye el mensaje de correo estándar.
        $nameValue = $notifiable->getAttribute('name');
        $displayName = is_string($nameValue) ? $nameValue : 'Usuario';

        return (new MailMessage)
            ->subject('Notificación de Restablecimiento de Contraseña')
            ->greeting(sprintf('¡Hola, %s!', $displayName))
            ->line('Estás recibiendo este correo porque hemos recibido una solicitud de restablecimiento de contraseña para tu cuenta.')
            ->action('Restablecer Contraseña', $this->resetUrl($notifiable))
            ->line(sprintf(
                'Este enlace de restablecimiento de contraseña expirará en %d minutos.',
                $expirationInMinutes
            ))
            ->line('Si no solicitaste este cambio, puedes ignorar este mensaje de forma segura.')
            ->line('Este correo electrónico es generado automáticamente. Por favor, no respondas a este mensaje.');
    }

    /**
     * Obtiene la representación de la notificación como un array.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $defaultBroker = config('auth.defaults.passwords');
        $broker = is_string($defaultBroker)
            ? $defaultBroker
            : 'users';
        $expireRaw = config('auth.passwords.'.$broker.'.expire');
        $expirationInMinutes = is_numeric($expireRaw)
            ? (int) $expireRaw
            : 60;

        return [
            'message' => 'Se ha solicitado un restablecimiento de contraseña.',
            'expires_at' => now()->addMinutes($expirationInMinutes)->toIso8601String(),
        ];
    }

    /**
     * Genera la URL de restablecimiento de contraseña para el usuario notificado.
     *
     * @param  Model&Authenticatable&CanResetPassword  $notifiable
     */
    private function resetUrl(object $notifiable): string
    {
        $email = $notifiable->getEmailForPasswordReset();

        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $email,
        ], false));
    }
}

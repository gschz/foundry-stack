<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación de seguridad enviada cuando se detecta un inicio de sesión desde un nuevo dispositivo.
 */
final class AccountLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * La fecha y hora en que ocurrió el inicio de sesión.
     */
    public \Carbon\CarbonInterface $time;

    /**
     * Crea una nueva instancia de la notificación.
     *
     * @param  string|null  $ipAddress  La dirección IP desde la que se originó el inicio de sesión.
     * @param  string|null  $userAgent  El agente de usuario (navegador/dispositivo) del inicio de sesión.
     * @param  string  $location  La ubicación geográfica aproximada del inicio de sesión.
     * @param  int|null  $loginId  El ID del registro de inicio de sesión para permitir marcarlo como confiable.
     */
    public function __construct(
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public string $location = 'Ubicación desconocida',
        public ?int $loginId = null
    ) {
        $this->time = now();
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
     * @param  Model&Authenticatable  $notifiable  La entidad que recibe la notificación (generalmente el usuario).
     * @return MailMessage El mensaje de correo electrónico configurado.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // --- Construcción del Mensaje Principal ---
        $nameValue = $notifiable->getAttribute('name');
        $displayName = is_string($nameValue) ? $nameValue : 'Usuario';

        $message = (new MailMessage)
            ->subject('¡Alerta de seguridad! Nuevo dispositivo detectado')
            ->greeting(sprintf('¡Hola %s!', $displayName))
            ->line('**Hemos detectado un inicio de sesión desde un dispositivo o ubicación que no habías usado antes.**')
            ->line('Si fuiste tú, no hay problema. Si no reconoces esta actividad, tu cuenta podría estar en riesgo.');

        // --- Detalles del inicio de sesión ---
        $message->line('**Detalles del inicio de sesión:**')
            ->line(
                sprintf(
                    '- **Fecha y hora:** %s',
                    now()->format('d/m/Y H:i:s')
                )
            );

        if (! in_array($this->ipAddress, [null, '', '0'], true)) {
            $message->line(
                sprintf(
                    '- **Dirección IP:** %s',
                    $this->ipAddress
                )
            );
        }

        if (! in_array($this->userAgent, [null, '', '0'], true)) {
            $message->line(
                sprintf(
                    '- **Dispositivo:** %s',
                    $this->userAgent
                )
            );
        }

        if (! in_array($this->location, [null, '', '0'], true)) {
            $message->line(
                sprintf(
                    '- **Ubicación aproximada:** %s',
                    $this->location
                )
            );
        }

        $message->line('Si no reconoces este acceso, te recomendamos cambiar tu contraseña inmediatamente.');

        return $message;
    }
}

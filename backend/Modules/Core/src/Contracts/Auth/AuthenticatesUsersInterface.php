<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Interfaz para la autenticación de usuarios.
 *
 * Define los métodos para gestionar el inicio de sesión, logout y obtención del usuario actual.
 */
interface AuthenticatesUsersInterface
{
    /**
     * Intenta autenticar a un usuario con las credenciales proporcionadas.
     *
     * @param  array<string, mixed>  $credentials  Credenciales de acceso.
     * @param  bool  $remember  Si se debe recordar la sesión ("remember me").
     * @return bool Verdadero si la autenticación fue exitosa.
     */
    public function attempt(array $credentials, bool $remember = false): bool;

    /**
     * Cierra la sesión del usuario actual.
     */
    public function logout(): void;

    /**
     * Obtiene el usuario autenticado actual.
     *
     * @return Authenticatable|null El usuario autenticado o null.
     */
    public function user(): ?Authenticatable;

    /**
     * Verifica si hay un usuario autenticado.
     */
    public function check(): bool;

    /**
     * Obtiene el ID del usuario autenticado.
     *
     * @return int|string|null
     */
    public function id();
}

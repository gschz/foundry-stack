<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Modules\Core\Domain\Addon\AddonInstance;
use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser as User;
use Nwidart\Modules\Laravel\Module;

/**
 * Interfaz para el registro y acceso a módulos del sistema.
 *
 * Define cómo se registran, consultan y gestionan los módulos disponibles.
 */
interface AddonRegistryInterface
{
    /**
     * Obtiene los módulos disponibles para un usuario específico según sus permisos.
     *
     * @param  User  $user  Usuario para el que se consultan los módulos disponibles
     * @return array<Module> Array de módulos a los que el usuario tiene acceso
     */
    public function getAvailableAddonsForUser(User $user): array;

    /**
     * Obtiene los módulos accesibles basados en el usuario actual o todos si no se proporciona usuario.
     *
     * @param  User|null  $user  Usuario para el que se consultan los módulos (o null para todos)
     * @return array<Module> Array de módulos accesibles
     */
    public function getAccessibleAddons(?User $user = null): array;

    /**
     * Obtiene todos los módulos habilitados sin filtrar por usuario.
     *
     * @return array<Module> Array de módulos habilitados
     */
    public function getAllEnabledAddons(): array;

    /**
     * Obtiene la configuración de un módulo específico por su nombre.
     *
     * @param  string  $moduleName  Nombre del módulo
     * @return array<string, mixed> Configuración del módulo
     */
    public function getAddonConfig(string $moduleName): array;

    /**
     * Obtiene un addon como entidad de dominio con configuración normalizada.
     *
     * Ejemplo:
     * - $addon = Addon::getAddonInstance('Admin');
     *
     * @param  string  $moduleName  Nombre del módulo/addon.
     * @return AddonInstance|null Instancia del addon o null si no existe su configuración.
     *
     * @throws InvalidAddonConfig Si la configuración del addon es inválida.
     */
    public function getAddonInstance(string $moduleName): ?AddonInstance;

    /**
     * Lista addons habilitados como instancias de dominio.
     *
     * Ejemplo:
     * - $addons = Addon::getAllEnabledAddonInstances();
     *
     * @return list<AddonInstance> Addons habilitados con su configuración normalizada.
     *
     * @throws InvalidAddonConfig Si la configuración de un addon es inválida.
     */
    public function getAllEnabledAddonInstances(): array;

    /**
     * Limpia la caché de configuraciones de módulos.
     */
    public function clearConfigCache(): void;

    /**
     * Obtiene los ítems de navegación global disponibles para un usuario.
     *
     * @param  User|null  $user  Usuario autenticado
     * @return array<int, array<string, mixed>> Ítems de navegación global
     */
    public function getGlobalNavItems(?User $user = null): array;
}

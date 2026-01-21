<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Addon;

/**
 * Value object para la configuración declarativa de un addon.
 *
 * Centraliza normalización y acceso a metadata clave (slug, guard, permisos base y rutas)
 * sin acoplar el dominio a detalles de Infrastructure o del framework.
 */
final readonly class AddonConfig
{
    /**
     * @param  string  $moduleSlug  Slug del addon (ej. "admin", "module01").
     * @param  string  $functionalName  Nombre funcional a mostrar en UI.
     * @param  string|null  $description  Descripción funcional del addon.
     * @param  string|null  $authGuard  Guard requerido para acceder (si aplica).
     * @param  string|null  $basePermission  Permiso base requerido (si aplica).
     * @param  string  $inertiaViewDirectory  Carpeta base de vistas Inertia.
     * @param  array<string, mixed>  $raw  Config original del addon.
     *
     * @throws InvalidAddonConfig Si el slug o el directorio de vistas son inválidos.
     */
    public function __construct(
        public string $moduleSlug,
        public string $functionalName,
        public ?string $description,
        public ?string $authGuard,
        public ?string $basePermission,
        public string $inertiaViewDirectory,
        public array $raw
    ) {
        throw_if(
            $this->moduleSlug === '',
            InvalidAddonConfig::class,
            'El addon requiere un moduleSlug no vacío.'
        );

        throw_if(
            $this->inertiaViewDirectory === '',
            InvalidAddonConfig::class,
            'El addon requiere un inertiaViewDirectory no vacío.'
        );
    }

    /**
     * Construye una configuración normalizada desde un array.
     *
     * Ejemplo:
     * - AddonConfig::fromArray('Admin', config('admin'));
     *
     * @param  string  $moduleName  Nombre del módulo (StudlyCase) usado como fallback.
     * @param  array<string, mixed>  $config  Config declarativa del módulo.
     * @return self Configuración normalizada.
     *
     * @throws InvalidAddonConfig Si la configuración normalizada es inválida.
     */
    public static function fromArray(string $moduleName, array $config): self
    {
        $slug = $config['module_slug'] ?? null;
        $moduleSlug = is_string($slug) && $slug !== ''
            ? $slug
            : mb_strtolower($moduleName);

        $fn = $config['functional_name'] ?? null;
        $functionalName = is_string($fn) && $fn !== ''
            ? $fn
            : ucfirst($moduleSlug);

        $desc = $config['description'] ?? null;
        $description = is_string($desc) && $desc !== ''
            ? $desc
            : null;

        $guard = $config['auth_guard'] ?? null;
        $authGuard = is_string($guard) && $guard !== ''
            ? $guard
            : null;

        $perm = $config['base_permission'] ?? null;
        $basePermission = is_string($perm) && $perm !== ''
            ? $perm
            : null;

        $viewDir = $config['inertia_view_directory'] ?? null;
        $inertiaViewDirectory = is_string($viewDir) && $viewDir !== ''
            ? $viewDir
            : $moduleSlug;

        return new self(
            moduleSlug: $moduleSlug,
            functionalName: $functionalName,
            description: $description,
            authGuard: $authGuard,
            basePermission: $basePermission,
            inertiaViewDirectory: $inertiaViewDirectory,
            raw: $config
        );
    }

    /**
     * Indica si el guard configurado es válido según la lista disponible.
     *
     * @param  array<int, string>  $availableGuards  Lista de guards definidos por la app.
     * @return bool True si el guard es válido o no se especifica; false si es inválido.
     */
    public function isValidGuard(array $availableGuards): bool
    {
        if ($this->authGuard === null) {
            return true;
        }

        return in_array($this->authGuard, $availableGuards, true);
    }
}

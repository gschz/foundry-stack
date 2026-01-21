<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Addon;

/**
 * Entidad de dominio que representa un addon habilitado con su configuración.
 */
final readonly class AddonInstance
{
    /**
     * @param  string  $name  Nombre del módulo/addon (ej. "Admin", "Module01").
     * @param  AddonConfig  $config  Configuración declarativa normalizada del addon.
     *
     * @throws InvalidAddonConfig Si el nombre es inválido.
     */
    public function __construct(
        public string $name,
        public AddonConfig $config
    ) {
        throw_if(
            $this->name === '',
            InvalidAddonConfig::class,
            'El addon requiere un name no vacío.'
        );
    }
}

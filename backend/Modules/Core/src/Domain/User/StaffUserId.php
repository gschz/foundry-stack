<?php

declare(strict_types=1);

namespace Modules\Core\Domain\User;

use InvalidArgumentException;

/**
 * Value Object para el identificador de usuario interno (Staff).
 *
 * Garantiza un ID no vacío y provee utilidades de creación/comparación.
 */
final readonly class StaffUserId
{
    /**
     * @param  string  $value  Identificador del usuario staff
     *
     * @throws InvalidArgumentException Si el ID es vacío
     */
    public function __construct(private string $value)
    {
        throw_if(
            $this->value === '',
            InvalidArgumentException::class,
            'Invalid StaffUserId'
        );
    }

    /**
     * Crea un StaffUserId a partir de un entero.
     *
     * @param  int  $id  Identificador numérico
     */
    public static function fromInt(int $id): self
    {
        return new self((string) $id);
    }

    /**
     * Crea un StaffUserId a partir de una cadena.
     *
     * @param  string  $id  Identificador como cadena
     */
    public static function fromString(string $id): self
    {
        return new self($id);
    }

    /**
     * Compara dos identificadores.
     *
     * @param  self  $other  Otro identificador de usuario staff
     * @return bool True si son idénticos; False en caso contrario
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Devuelve el ID como cadena.
     */
    public function toString(): string
    {
        return $this->value;
    }
}

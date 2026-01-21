<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Addon;

use InvalidArgumentException;
use Throwable;

/**
 * Excepción lanzada cuando la configuración de un addon es inválida.
 */
final class InvalidAddonConfig extends InvalidArgumentException
{
    /**
     * @param  string  $message  Mensaje descriptivo del error.
     * @param  int  $code  Código de error opcional.
     * @param  Throwable|null  $previous  Excepción previa encadenada (si aplica).
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

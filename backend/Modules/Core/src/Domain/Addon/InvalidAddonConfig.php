<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Addon;

use InvalidArgumentException;

/**
 * Excepción lanzada cuando la configuración de un addon es inválida.
 */
final class InvalidAddonConfig extends InvalidArgumentException {}

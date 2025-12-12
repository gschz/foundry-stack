<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Infrastructure\Laravel\Facades\Mod;

/**
 * Controlador base para las páginas de configuración.
 * Proporciona funcionalidades compartidas, como la construcción del menú de navegación.
 */
abstract class BaseSettingsController extends Controller
{
    public function __construct()
    {
        //
    }

    /**
     * Obtiene los ítems de navegación para el menú de configuración.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getSettingsNavigationItems(): array
    {
        return Mod::getGlobalNavItems(
            Auth::guard('staff')->user()
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Infrastructure\Laravel\Facades\Addon;
use Modules\Core\Infrastructure\Laravel\Facades\Menu;

/**
 * Controlador base para las páginas de perfil.
 * Proporciona funcionalidades compartidas, como la construcción del menú de navegación.
 */
abstract class AbstractProfileController extends Controller
{
    public function __construct()
    {
        //
    }

    /**
     * Obtiene los ítems de navegación para el menú de perfil.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getProfileNavigationItems(): array
    {
        return Addon::getGlobalNavItems(
            Auth::guard('staff')->user()
        );
    }

    /**
     * Construye breadcrumbs configurados para rutas de perfil.
     *
     * @param  string  $routeSuffix  Sufijo de ruta (ej. 'profile.edit', 'password.edit')
     * @return array<int, array<string, mixed>>
     */
    protected function buildBreadcrumbs(string $routeSuffix): array
    {
        return Menu::buildConfiguredBreadcrumbs('core', $routeSuffix);
    }
}

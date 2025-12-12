<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Http\Controllers\Settings;

use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Infrastructure\Laravel\Facades\Nav;

final class AppearanceController extends BaseSettingsController
{
    /**
     * Muestra la página de configuración de apariencia.
     */
    public function show(): Response
    {
        $breadcrumbs = Nav::buildConfiguredBreadcrumbs(
            'core',
            'user.settings.appearance'
        );

        return Inertia::render('settings/appearance', [
            'contextualNavItems' => $this->getSettingsNavigationItems(),
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}

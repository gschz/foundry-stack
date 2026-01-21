<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Http\Controllers\Profile;

use Inertia\Inertia;
use Inertia\Response;

final class AppearanceController extends AbstractProfileController
{
    /**
     * Muestra la página de configuración de apariencia.
     */
    public function show(): Response
    {
        $breadcrumbs = $this->buildBreadcrumbs('appearance');

        return Inertia::render('profile/appearance', [
            'contextualNavItems' => $this->getProfileNavigationItems(),
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Facades\RouteFilter;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Modules\Core\Application\View\ComposeInertiaProps;

final class HandleInertiaRequests extends Middleware
{
    /**
     * La plantilla raíz que se carga en la primera visita a la página.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(
        private readonly ComposeInertiaProps $composeInertiaProps
    ) {
        //
    }

    /**
     * Determina la versión actual de los assets.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define las props que se comparten por defecto con todas las vistas de Inertia.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $baseProps = [
            'name' => config('app.name', 'Foundry Stack'),
            'ziggy' => fn () => RouteFilter::getFilteredZiggy($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                'warning' => fn () => $request->session()->get('warning'),
                'credentials' => fn () => $request->session()->get('credentials'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state')
                || $request->cookie('sidebar_state') === 'true',

        ];

        $sharedData = parent::share($request);

        // Usar la nueva acción de composición del módulo Core
        $coreProps = $this->composeInertiaProps->execute($request);

        return array_merge($baseProps, $coreProps, $sharedData);
    }
}

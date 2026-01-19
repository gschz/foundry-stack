<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

/**
 * Interfaz para orquestar el renderizado de paneles y vistas de módulos.
 *
 * Centraliza la preparación de contexto (navegación, breadcrumbs, permisos, stats)
 * sin requerir herencia desde controladores de Infrastructure en los módulos.
 */
interface ModuleOrchestratorInterface
{
    /**
     * Resuelve el usuario autenticado aplicando el guard del módulo (si existe).
     *
     * Ejemplo:
     * - $user = $orchestrator->resolveAuthenticatedUser($request, 'module01');
     *
     * @param  Request  $request  Request HTTP actual.
     * @param  string  $moduleSlug  Slug del módulo.
     * @param  array<string, mixed>|null  $moduleConfig  Config del módulo (opcional).
     * @return Authenticatable|null Usuario autenticado o null.
     */
    public function resolveAuthenticatedUser(
        Request $request,
        string $moduleSlug,
        ?array $moduleConfig = null
    ): ?Authenticatable;

    /**
     * Renderiza una vista Inertia del módulo con el contexto estándar del proyecto.
     *
     * @param  Request  $request  Request HTTP actual.
     * @param  string  $moduleSlug  Slug del módulo.
     * @param  array<string, mixed>|null  $moduleConfig  Config del módulo (opcional).
     * @param  array<string, mixed>  $additionalData  Props adicionales específicas de la vista.
     * @param  array<int, array<string, mixed>>|null  $customPanelItems  Config de panel personalizada.
     * @param  array<int, array<string, mixed>>|null  $customNavItems  Config de nav contextual personalizada.
     * @param  string|null  $routeSuffix  Sufijo de ruta para seleccionar configuración (opcional).
     * @param  array<string, mixed>  $routeParams  Parámetros de ruta.
     * @param  array<string, mixed>  $dynamicTitleData  Props para títulos dinámicos (breadcrumbs).
     * @param  MenuBuilderInterface|null  $navigationService  Servicio para resolver referencias en config.
     * @param  string  $view  Nombre de la vista (por defecto: 'index').
     * @return InertiaResponse Respuesta Inertia lista para el frontend.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException Si el usuario no está autenticado.
     */
    public function renderModuleView(
        Request $request,
        string $moduleSlug,
        ?array $moduleConfig = null,
        array $additionalData = [],
        ?array $customPanelItems = null,
        ?array $customNavItems = null,
        ?string $routeSuffix = null,
        array $routeParams = [],
        array $dynamicTitleData = [],
        ?MenuBuilderInterface $navigationService = null,
        string $view = 'index'
    ): InertiaResponse;
}

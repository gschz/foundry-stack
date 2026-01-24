<?php

declare(strict_types=1);

namespace Modules\Module01\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Core\Contracts\MenuBuilderInterface;
use Modules\Core\Contracts\ModuleOrchestratorInterface;
use Modules\Core\Contracts\StatsServiceInterface;

/**
 * Controlador base del Módulo 01.
 */
abstract class AbstractModule01Controller extends Controller
{
    /**
     * Slug del módulo (configurable en el módulo).
     */
    protected string $moduleSlug;

    /**
     * Inyecta el orquestador y servicios transversales del Core
     * para mantener un controlador delgado y reutilizable.
     *
     * @param  ModuleOrchestratorInterface  $orchestrator  {@inheritdoc}
     * @param  MenuBuilderInterface  $navigationBuilder  {@inheritdoc}
     * @param  StatsServiceInterface  $statsService  {@inheritdoc}
     */
    public function __construct(
        protected readonly ModuleOrchestratorInterface $orchestrator,
        protected readonly MenuBuilderInterface $navigationBuilder,
        protected readonly StatsServiceInterface $statsService
    ) {
        $this->moduleSlug = $this->resolveModuleSlug();
    }

    /**
     * @return string Slug del módulo configurado.
     */
    protected function getModuleSlug(): string
    {
        return $this->moduleSlug;
    }

    /**
     * Resuelve el slug del módulo desde la configuración o un valor predeterminado.
     */
    private function resolveModuleSlug(): string
    {
        $configured = config('module01.module_slug');

        return is_string($configured) && $configured !== ''
            ? $configured
            : 'module01';
    }
}

<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;
use Modules\Core\Infrastructure\Laravel\Services\ModuleRegistryService;
use Nwidart\Modules\Laravel\Module;

/**
 * Facade para el registro de módulos del sistema.
 *
 * @method static list<Module> getAvailableModulesForUser(StaffUser $user)
 * @method static list<Module> getAccessibleModules(?StaffUser $user = null)
 * @method static list<Module> getAllEnabledModules()
 * @method static array<string, mixed> getModuleConfig(string $moduleName)
 * @method static array<int, array<string, mixed>> getGlobalNavItems(?StaffUser $user = null)
 * @method static void clearConfigCache()
 *
 * @see ModuleRegistryService
 */
final class Mod extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ModuleRegistryService::class;
    }
}

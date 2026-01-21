<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\Contracts\AddonRegistryInterface;
use Modules\Core\Domain\Addon\AddonInstance;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser as User;
use Nwidart\Modules\Laravel\Module;

/**
 * Facade para el registro de módulos del sistema.
 *
 * @method static list<Module> getAvailableAddonsForUser(User $user)
 * @method static list<Module> getAccessibleAddons(?User $user = null)
 * @method static list<Module> getAllEnabledAddons()
 * @method static array<string, mixed> getAddonConfig(string $moduleName)
 * @method static AddonInstance|null getAddonInstance(string $moduleName)
 * @method static list<AddonInstance> getAllEnabledAddonInstances()
 * @method static array<int, array<string, mixed>> getGlobalNavItems(?User $user = null)
 * @method static void clearConfigCache()
 *
 * @see AddonRegistryInterface
 */
final class Addon extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AddonRegistryInterface::class;
    }
}

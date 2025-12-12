<?php

declare(strict_types=1);

// Proveedores base de la aplicación
$baseProviders = [
    App\Providers\EarlyBindingsServiceProvider::class,
    App\Providers\RuntimeConfigServiceProvider::class,
    NunoMaduro\Essentials\EssentialsServiceProvider::class,
    Illuminate\Cache\CacheServiceProvider::class,
    Illuminate\Translation\TranslationServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\SessionServiceProvider::class,
];

// Descubrir proveedores de módulos activos según modules_statuses.json
$modulesDir = __DIR__.'/../Modules';
$statusesPath = dirname(__DIR__).'/modules_statuses.json';
$activeModules = [];
if (is_file($statusesPath)) {
    $json = json_decode((string) file_get_contents($statusesPath), true);
    if (is_array($json)) {
        foreach ($json as $moduleName => $enabled) {
            if ($enabled) {
                $activeModules[] = (string) $moduleName;
            }
        }
    }
}

$moduleProviders = [];
foreach ($activeModules as $module) {
    $providerPath = $modulesDir.'/'.$module.'/app/Providers/'.$module.'ServiceProvider.php';
    if (is_file($providerPath)) {
        $moduleProviders[] = sprintf(
            'Modules\%s\App\Providers\%sServiceProvider',
            $module,
            $module
        );
    }
}

return array_values(
    array_unique(array_merge($baseProviders, $moduleProviders))
);

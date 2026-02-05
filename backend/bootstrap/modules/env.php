<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Illuminate\Foundation\Application;
use Illuminate\Support\Env;

return function (Application $application): void {
    // 1. Resolver Raíz del Proyecto (Monorepo)
    $projectRootRaw = dirname($application->basePath());
    $projectRoot = realpath($projectRootRaw) ?: $projectRootRaw;

    // 2. Detectar Contexto de Ejecución
    $isCli = PHP_SAPI === 'cli';
    $argvString = isset($_SERVER['argv'])
        ? implode(' ', (array) $_SERVER['argv']) : '';
    $isPhpUnit = $isCli && ($argvString !== '')
        && str_contains($argvString, 'phpunit');

    $runningInContainerEnv = Env::get(
        'APP_RUNNING_IN_CONTAINER',
        $_SERVER['APP_RUNNING_IN_CONTAINER'] ?? null
    );
    $runningInContainer = filter_var(
        $runningInContainerEnv,
        FILTER_VALIDATE_BOOL
    ) ?: (is_file('/.env.docker'));

    $appEnv = Env::get('APP_ENV', $_SERVER['APP_ENV'] ?? null);

    // 3. Definición de Archivos de Entorno (Hardcoded Estándar)
    $envDir = '.envs';
    $envMap = [
        'testing' => $envDir.DIRECTORY_SEPARATOR.'.env.testing',
        'docker' => $envDir.DIRECTORY_SEPARATOR.'.env.docker',
        'production' => $envDir.DIRECTORY_SEPARATOR.'.env.production.local',
        'local' => $envDir.DIRECTORY_SEPARATOR.'.env.local',
    ];
    $required = ['APP_ENV', 'APP_KEY'];

    // 4. Determinar Archivo de Entorno (Lógica Explícita)
    $envFileName = null;
    $context = 'default';

    // Prioridad 1: Override Explícito (ej. Bun --env-file inyectando LARAVEL_ENV_FILE)
    if ($explicit = Env::get('LARAVEL_ENV_FILE', $_SERVER['LARAVEL_ENV_FILE'] ?? null)) {
        $envFileName = $explicit;
        $context = 'explicit (LARAVEL_ENV_FILE)';
    }
    // Prioridad 2: Entorno de Pruebas
    elseif ($isPhpUnit || $appEnv === 'testing') {
        $envFileName = $envMap['testing'];
        $context = 'testing';
    }
    // Prioridad 3: Contenedor Docker
    elseif ($runningInContainer) {
        $envFileName = $envMap['docker'];
        $context = 'docker';
    }
    // Prioridad 4: Producción
    elseif ($appEnv === 'production') {
        $envFileName = $envMap['production'];
        $context = 'production';
    }
    // Prioridad 5: Desarrollo Local (Por Defecto o Fallo de Configuración)
    else {
        // Validación Estricta:
        // Si detectamos que YA existen variables críticas en el entorno (inyectadas por Bun, Docker mal configurado, etc.)
        // pero NO tenemos un LARAVEL_ENV_FILE explícito, asumimos una configuración rota y fallamos.
        if (
            Env::get('APP_KEY') !== null
            || (Env::get('APP_ENV') !== null && Env::get('APP_ENV') !== 'production')
        ) {
            $msg = "\n[FATAL] Configuración de entorno ambigua detectada.\n".
                "Se encontraron variables de entorno inyectadas pero falta 'LARAVEL_ENV_FILE'.\n";
            defined('STDERR') ? fwrite(STDERR, $msg) : error_log($msg);
            exit(1);
        }

        $envFileName = $envMap['local'];
        $context = 'local (default fallback)';
    }

    // 5. Validar Existencia del Archivo (Sin Fallbacks Silenciosos)
    throw_unless(
        is_string($envFileName),
        RuntimeException::class,
        'No se pudo determinar el nombre del archivo de entorno.'
    );

    $envPath = $projectRoot.DIRECTORY_SEPARATOR.$envFileName;

    if (! file_exists($envPath)) {
        $msg = sprintf(
            "\n[FATAL] Archivo de entorno no encontrado para el contexto '%s'.\n".
                "Ruta esperada: %s\n",
            $context,
            $envPath
        );

        if (defined('STDERR')) {
            fwrite(STDERR, $msg);
        } else {
            error_log($msg);
        }

        if (! $isPhpUnit) {
            exit(1);
        }
    }

    // 6. Cargar Variables de Entorno
    if (is_file($envPath) && is_readable($envPath)) {
        try {
            Dotenv::createImmutable($projectRoot, $envFileName)->safeLoad();
            $application->loadEnvironmentFrom(
                '..'.DIRECTORY_SEPARATOR.$envFileName
            );
        } catch (Throwable $e) {
            $msg = sprintf(
                "\n[FATAL] Error al cargar el archivo de entorno: %s\n",
                $e->getMessage()
            );
            if (defined('STDERR')) {
                fwrite(STDERR, $msg);
            }

            exit(1);
        }
    }

    // 7. Validar Variables Requeridas
    foreach ($required as $key) {
        $val = Env::get($key, $_SERVER[$key] ?? null);
        if ($val === null || $val === '') {
            $msg = sprintf(
                "\n[FATAL] Variable de entorno requerida ausente: %s\n",
                $key
            );
            if (defined('STDERR')) {
                fwrite(STDERR, $msg);
            }

            exit(1);
        }
    }
};

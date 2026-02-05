import { copyFileSync, existsSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { join, resolve } from 'node:path';
import chalk from 'chalk';
import { downloadTemplate } from 'giget';
import ora from 'ora';
import { cleanupSqliteArtifacts, ensureSqliteDatabase } from '../utils/db';
import { generateAppKey } from '../utils/env';
import { runCommand } from '../utils/run';
import type { InstallerOptions } from './interactive';

// URL del repositorio template
const TEMPLATE_REPO = 'github:gschz/foundry-stack';

export const installProject = async (options: InstallerOptions) => {
  const { projectName } = options;
  if (!projectName) {
    console.error(chalk.red('Nombre del proyecto no especificado.'));
    process.exit(1);
  }

  const projectDir = resolve(process.cwd(), projectName);

  try {
    const spinner = ora({
      text: chalk.gray('Iniciando instalación...'),
      color: 'yellow',
    }).start();

    // 1. Clonar Template
    spinner.start(chalk.gray(`Descargando template en ${chalk.bold(projectName)}...`));
    try {
      await downloadTemplate(TEMPLATE_REPO, {
        dir: projectDir,
        force: true, // Sobrescribir si existe (giget maneja esto, cuidado)
        // preferOffline: true // Opcional
      });
      spinner.succeed(chalk.gray('Template descargado'));
    } catch (error) {
      spinner.fail(chalk.red('Error descargando el template'));
      throw error;
    }

    // Cambiar el directorio de trabajo para los comandos subsiguientes

    // 1.1. Aplicar preferencias de entornos opcionales (Docker, PostgreSQL)
    const envs = options.environments ?? { docker: false, postgres: false };

    const pruneDocker = () => {
      try {
        rmSync(join(projectDir, 'docker'), { recursive: true, force: true });
        rmSync(join(projectDir, '.envs', '.env.docker.example'), { force: true });
        rmSync(join(projectDir, '.envs', '.env.docker'), { force: true });
        rmSync(join(projectDir, 'docker-compose.yml'), { force: true });
        rmSync(join(projectDir, 'docker-compose.override.yml'), { force: true });
      } catch {}

      try {
        const pkgPath = join(projectDir, 'package.json');
        const pkg = JSON.parse(readFileSync(pkgPath, 'utf8'));
        const scripts = pkg.scripts ?? {};
        const keysToRemove = Object.keys(scripts).filter((k) => k.startsWith('dk') || k === 'dev:fe:dk');
        for (const key of keysToRemove) delete scripts[key];
        pkg.scripts = scripts;
        writeFileSync(pkgPath, JSON.stringify(pkg, null, 2));
      } catch {}
    };

    const prunePostgres = () => {
      try {
        rmSync(join(projectDir, '.envs', '.env.pg.local.example'), { force: true });
        rmSync(join(projectDir, '.envs', '.env.pg.local'), { force: true });
      } catch {}

      // Eliminar scripts pg:* en raíz
      try {
        const rootPkgPath = join(projectDir, 'package.json');
        const rootPkg = JSON.parse(readFileSync(rootPkgPath, 'utf8'));
        const rootScripts = rootPkg.scripts ?? {};
        for (const key of Object.keys(rootScripts)) if (key.startsWith('pg')) delete rootScripts[key];
        rootPkg.scripts = rootScripts;
        writeFileSync(rootPkgPath, JSON.stringify(rootPkg, null, 2));
      } catch {}

      // Eliminar scripts pg:* en backend
      try {
        const bePkgPath = join(projectDir, 'backend', 'package.json');
        const bePkg = JSON.parse(readFileSync(bePkgPath, 'utf8'));
        const beScripts = bePkg.scripts ?? {};
        for (const key of Object.keys(beScripts)) if (key.startsWith('pg')) delete beScripts[key];
        bePkg.scripts = beScripts;
        writeFileSync(bePkgPath, JSON.stringify(bePkg, null, 2));
      } catch {}
    };

    // Ejecutar pruning según selección
    if (!envs.docker) pruneDocker();
    if (!envs.postgres) prunePostgres();

    // 2. Configurar entorno
    spinner.start(chalk.gray('Configurando entorno...'));
    const envLocalPath = join(projectDir, '.envs', '.env.local');
    const envExamplePath = join(projectDir, '.envs', '.env.local.example');

    if (!existsSync(envLocalPath)) {
      if (existsSync(envExamplePath)) {
        copyFileSync(envExamplePath, envLocalPath);

        const envContent = readFileSync(envLocalPath, 'utf8');
        const appKey = generateAppKey();
        const updatedContent = envContent.includes('APP_KEY=')
          ? envContent.replace('APP_KEY=', `APP_KEY=${appKey}`)
          : `${envContent.trimEnd()}\nAPP_KEY=${appKey}\n`;
        writeFileSync(envLocalPath, updatedContent, 'utf8');
        spinner.succeed(chalk.gray('Archivo .envs/.env.local creado y configurado'));
      } else {
        spinner.warn(chalk.yellow('No se encontró .envs/.env.local.example, saltando creación de .envs/.env.local'));
      }
    } else {
      spinner.info(chalk.gray('Archivo .envs/.env.local ya existe'));
    }

    // 3. Instalar dependencias
    spinner.start(chalk.gray('Instalando dependencias (esto puede tardar unos minutos)...'));
    try {
      runCommand('bun run i:all', 'Instalación del workspace', projectDir);
      spinner.succeed(chalk.gray('Dependencias instaladas'));
    } catch (error) {
      spinner.fail(chalk.red('Error instalando dependencias'));
      throw error;
    }

    // Limpieza de cachés
    spinner.start(chalk.gray('Limpiando cachés...'));
    try {
      runCommand('php backend/artisan config:clear', 'Limpiando configuración', projectDir);
      runCommand('php backend/artisan cache:clear', 'Limpiando caché', projectDir);
      spinner.succeed(chalk.gray('Cachés limpiadas'));
    } catch {
      spinner.warn(chalk.yellow('No se pudieron limpiar las cachés (normal en primera instalación)'));
    }

    // 4. Base de datos y Migraciones
    spinner.start(chalk.gray('Configurando base de datos...'));

    // Asegurar archivo SQLite antes de ejecutar migraciones
    ensureSqliteDatabase(projectDir);

    try {
      runCommand('bun run be migrate:fresh:seed', 'Migraciones y Seeders', projectDir);
      spinner.succeed(chalk.gray('Base de datos configurada y poblada'));
    } catch (error) {
      spinner.fail(chalk.red('Error en migraciones'));
      throw error;
    }

    // 5. Verificar SQLite
    cleanupSqliteArtifacts(projectDir);

    // 6. Rutas Ziggy
    spinner.start(chalk.gray('Generando rutas Ziggy...'));
    runCommand('bun run be ziggy', 'Generación de rutas', projectDir);
    spinner.succeed(chalk.gray('Rutas generadas'));

    // 7. Optimización final
    spinner.start(chalk.gray('Optimizando aplicación...'));
    runCommand('bun run be clear:all', 'Limpieza final', projectDir);
    spinner.succeed(chalk.gray('Optimización completada'));

    spinner.stop();

    console.log(`\n${chalk.green('✓ Instalación completada exitosamente')}\n`);

    console.log(chalk.white('Para iniciar el proyecto:'));
    console.log(chalk.gray(`  $ cd ${projectName}`));
    console.log(`${chalk.hex('#f97316')('  $ bun dev')}\n`);

    console.log(chalk.white('Credenciales de Administrador:'));
    console.log(chalk.gray('  Email:    ') + chalk.white('admin@domain.com'));
    console.log(`${chalk.gray('  Password: ') + chalk.white('AdminPass123!')}\n`);
  } catch (error) {
    console.error(chalk.red('\nError durante la instalación:'));
    console.error(error);
    process.exit(1);
  }
};

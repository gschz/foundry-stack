# Changelog

Todos los cambios notables de Foundry Stack se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

> [!NOTE]
> Este proyecto aún no tiene releases/tags publicados. Este changelog documenta hitos del repositorio y se irá refinando cuando existan releases formales.

## [Unreleased]

### Added

### Changed

### Fixed

## [0.2.0-alpha] - 2026-01-31

### Added

- Core v2 (módulo más maduro del repo) con separación Domain/Application/Infrastructure/Contracts.
- Navegación dinámica y composición de props para Inertia desde Core.
- Soporte de instalación mediante `@foundry-stack/installer`.

### Changed

- Rebrand del proyecto a “Foundry Stack” (antes `laravel-react-modular-stack`).
- Archivos de entorno centralizados en `.envs/` y scripts alineados para cargar env correcto.
- Convenciones de rutas internas para staff (`internal.staff.*`).

### Fixed

- Sincronización de permisos entre guards en flujos de staff.
- Inconsistencias de invalidación de caché relacionadas con navegación/permisos.

## [0.1.0-alpha] - 2025-09-27

### Added

- Base inicial del repositorio (Laravel + React + Inertia) con workspaces.
- Core v1 (legacy): auth/permisos/navegación con servicios grandes y acoplados.
- Módulo Admin y primeros ejemplos de modularidad con nwidart/laravel-modules.
- Soporte de desarrollo local con SQLite.

### Known issues

- Core v1 resultó difícil de mantener/testear en aislamiento por el tamaño de servicios y el acoplamiento (motivación para Core v2).

## Estrategia de Versionado

- Foundry Stack usa SemVer con versiones `0.y.z` (desarrollo inicial): cambios incompatibles pueden ocurrir sin bump mayor.
- El módulo Core usa su versión independiente (actualmente `v2.0.0`) porque evoluciona con un ciclo distinto al “bundle” del repo.

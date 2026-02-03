# Arquitectura (alto nivel)

> **Estado:** Desarrollo activo (alpha)  
> **Última actualización:** 2026-01-31

Este documento describe la arquitectura actual a un nivel alto. La intención es que sirva como “mapa mental” para encontrar dónde vive cada cosa y cuáles son los límites importantes del sistema.

## Alcance

- Describe lo que existe hoy en el repositorio.
- No es una guía de instalación (ver [`INSTALLATION.md`](INSTALLATION.md)).
- No es un roadmap ni una lista de features futuras.
- Evita detalles finos que cambian seguido (config keys, firmas exactas, etc.).

## Vista general

Foundry Stack es un baseline para sistemas internos con:

- Backend Laravel organizado por módulos (`backend/Modules/*`).
- Frontend React (Vite) integrado con Inertia (props desde backend, UI en frontend).
- Entornos gestionados desde `.envs/`.
- Scripts orquestados con Bun (workspaces).

## Code map (dónde está qué)

**Raíz**

- `package.json`: scripts de orquestación (`bun dev`, `bun run be ...`, `bun run fe ...`, `bun run dk:*`).
- `.envs/`: archivos de entorno por escenario (local SQLite, PostgreSQL local, Docker).
- `database/`: migraciones/seeders “a nivel app” y SQLite local (archivo `database.sqlite`).
- `docs/`: documentación “estable” del proyecto (se mantiene mínima).

**Backend (`backend/`)**

- `backend/app/`: glue de Laravel (providers, middleware, requests).
- `backend/config/`: configuración; incluye el wiring de base de datos para SQLite/PG/Docker.
- `backend/Modules/`: módulos del sistema (nwidart/laravel-modules).
  - `Core`: módulo transversal (auth/permisos/navegación/vistas) con separación por capas.
  - `Admin`: módulo de administración (estructura más tradicional).
  - `Module01`, `Module02`: placeholders de ejemplo.

**Frontend (`frontend/`)**

- `frontend/src/`: UI React.
  - `pages/`: páginas Inertia (incluye pages por módulo).
  - `layouts/`, `components/`, `lib/`, `hooks/`: piezas compartidas.
- `frontend/vite.config.ts`: configuración de Vite; toma env desde `.envs/`.

**Paquetes (`packages/`)**

- `packages/foundry-installer/`: CLI `@foundry-stack/installer` para bootstrap del template.

## Flujos principales (runtime)

**UI (Inertia)**

1. Request a una ruta `internal.*`.
2. Controlador de módulo prepara datos (y delega en servicios transversales cuando aplica).
3. Se arma un payload de props para Inertia.
4. El frontend renderiza la página React correspondiente.

**Autenticación y autorización (staff)**

- El sistema usa un guard de staff y RBAC con Spatie.
- Los módulos declaran su permiso base (p.ej. `access-admin`) y el acceso se filtra en backend.

## Invariantes arquitectónicas (reglas que conviene no romper)

Estas reglas importan más que detalles puntuales, porque son las que evitan que el proyecto se vuelva difícil de mantener:

- En `Core`, la capa **Domain** no depende de Laravel (sin Eloquent, sin Facades, sin `Request`, sin `Cache`).
- En `Core`, la capa **Application** orquesta casos de uso y depende de Domain/Contracts, no de detalles de infraestructura.
- Los módulos existen como unidades “runtime” bajo `backend/Modules/*` (registro con nwidart). El sistema asume esa separación.
- La UI no “salta” al backend por rutas ad-hoc: la integración UI se hace por Inertia (y, si aplica, por endpoints API versionados).
- Los comandos de Artisan no se ejecutan “directo” si necesitas env correcto: se usan los scripts del backend (`bun run be artisan ...`, `bun run be pg ...`).

## Límites (boundaries) y puntos de integración

- **Frontend ↔ Backend:** frontera en Inertia (props serializados). Si algo “no cabe” en props, suele ser señal de que hace falta un endpoint dedicado o simplificar el payload.
- **Core ↔ módulos:** Core concentra responsabilidades transversales. Los módulos deberían consumirlas a través de contratos/servicios, no reimplementar su propia versión.
- **Entorno ↔ ejecución:** `.envs/` define configuración; los scripts (`package.json`, `backend/package.json`) son el mecanismo recomendado para inyectar ese env en runtime.

## Preocupaciones transversales (cross-cutting)

- Seguridad: middleware de headers (CSP, etc.), separación de rutas internas (`/internal/*`).
- Performance: caching (permisos/navegación) y invalidación por versión.
- DX: workspaces con Bun; comandos centralizados en `docs/COMMANDS.md`.
- QA backend: Pint + PHPStan + Pest + Rector (script `bun run be qa`).
- QA frontend: ESLint y TypeScript (scripts `bun run fe lint` y `bun run fe types`).

## “Dónde cambio X”

- **Agregar/editar scripts de desarrollo:** `package.json` (raíz) y `backend/package.json`.
- **Ajustar instalación/installer:** `packages/foundry-installer/`.
- **Cambiar autenticación/permisos/navegación:** `backend/Modules/Core/`.
- **Cambiar UI/páginas:** `frontend/src/pages/` y `frontend/src/layouts/`.
- **Agregar un módulo nuevo:** `backend/Modules/<ModuleName>/` + registro de nwidart (ver scripts de módulos en `docs/COMMANDS.md`).

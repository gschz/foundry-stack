<div align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)"  srcset=".github/assets/dark_banner_transparent_bg.svg" />
    <source media="(prefers-color-scheme: light)"  srcset=".github/assets/light_banner_transparent_bg.svg" />
    <img src=".github/assets/dark_banner_transparent_bg.svg" alt="Foundry Stack Banner" />
  </picture>
  <br>
</div>

> [!WARNING]
> Proyecto en desarrollo activo (alpha). Espera cambios significativos.

Foundry Stack es un monorepo con **Laravel 12** (backend) y **React 19** (frontend) integrados con **Inertia.js**. Incluye un módulo **Core v2** que concentra lógica transversal (auth, permisos, navegación/menú, perfil y seguridad) y una estructura modular con `nwidart/laravel-modules`.

## ¿Qué es esto?

Una base modular para sistemas de gestión internos con:

- **Módulo Core centralizado**: Auth, permisos cross-guard, navegación dinámica
- **Arquitectura modular**: Separación de features usando nwidart/laravel-modules
- **Enfoque staff-first**: Para usuarios internos (backoffice, paneles admin)
- **Tooling moderno**: Bun, Vite 7, TypeScript, Tailwind 4
- **Multi-entorno**: Local (SQLite), PostgreSQL, Docker

**No es un producto terminado**, es un experimento arquitectónico funcional.

## ¿Por qué existe?

Este repositorio es un proyecto de aprendizaje sobre arquitecturas modulares. La motivación es tener un baseline reutilizable para sistemas internos sin repetir la misma lógica transversal en cada proyecto.

Si estás aprendiendo arquitectura Laravel, puede ser útil. Si necesitas algo production-ready, busca alternativas (por ahora).

## Requisitos

- **Bun 1.3+**
- **PHP 8.4+** & Composer
- **Git**
- _Opcional_: Docker, PostgreSQL

## Instalación

La guía completa está en [INSTALLATION.md](docs/INSTALLATION.md).

### Setup con instalador

```bash
bunx @foundry-stack/installer
```

## Entornos (`.envs/`)

Los ejemplos de entorno viven en `.envs/`:

- Local (SQLite): `.envs/.env.local.example` → `.envs/.env.local`
- PostgreSQL local: `.envs/.env.pg.local.example` → `.envs/.env.pg.local`
- Docker: `.envs/.env.docker.example` → `.envs/.env.docker`

## Comandos esenciales

```bash
# Desarrollo (backend + frontend + queue)
bun dev

# Base de datos (SQLite)
bun run be migrate:fresh:seed

# QA backend (Pint + PHPStan + Pest + Rector)
bun run be qa

# Frontend
bun run fe lint
bun run fe types
```

Lista completa: [COMMANDS.md](docs/COMMANDS.md).

## Decisiones clave

**Rutas `internal.staff.*`**

- Distingue usuarios internos de futuros tipos (tenant/cliente).
- Evita colisiones y facilita políticas de seguridad.

**Core v2 centralizado**

- Concentra lógica transversal para que los módulos de negocio no dupliquen auth/permisos/menú.

**Hexagonal en Core (no en todo el repo)**

- Core aplica Domain/Application/Infrastructure/Contracts; módulos legacy mantienen estructura simple por ahora.

## Documentación

- Instalación: [INSTALLATION.md](docs/INSTALLATION.md)
- Arquitectura: [ARCHITECTURE.md](docs/ARCHITECTURE.md)
- Comandos: [COMMANDS.md](docs/COMMANDS.md)
- Historial de cambios: [CHANGELOG.md](CHANGELOG.md)

## Problemas conocidos

- Admin usa estructura legacy (pre-hexagonal).
- Module01/Module02 son placeholder (serán reemplazados).
- Documentación incompleta (en progreso).
- Sin soporte multi-tenant aún (idea para v3).

## Contribuir

Actualmente es un proyecto de aprendizaje individual, pero feedback y sugerencias son bienvenidas vía Issues o Discussions.

## Créditos

Este proyecto se inspira en el _React Starter Kit_ oficial de Laravel y extiende su base con una arquitectura modular orientada a back-office. Reconocimiento pleno a Laravel, a su ecosistema y a todas las librerías que hacen posible este proyecto.

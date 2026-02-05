# Instalación

Esta guía cubre la instalación y configuración de Foundry Stack para desarrollo.

## Requisitos

### Obligatorios

- **Bun**: ([Instalar Bun](https://bun.sh))
- **PHP**: ([Instalar PHP](https://www.php.net/downloads))
- **Composer**: ([Instalar Composer](https://getcomposer.org/download/))
- **Git**: Cualquier versión reciente

### Opcionales

- **PostgreSQL**
- **Docker**
- **Docker Compose**

---

## Opción A: Instalación con instalador (recomendada)

```bash
bunx @foundry-stack/installer
```

### Qué hace el instalador

El flujo de instalación está pensado para dejarte en un estado “listo para `bun dev`”:

- Descarga el template de Foundry Stack en una carpeta con el nombre del proyecto.
- Pregunta qué entornos adicionales habilitar (Docker y/o PostgreSQL local).
  - Si no habilitas alguno, elimina sus archivos y scripts para mantener el proyecto limpio.
- Crea el archivo de entorno local `.envs/.env.local` a partir de `.envs/.env.local.example` y setea un `APP_KEY`.
- Instala dependencias del monorepo (`bun run i:all`).
- Asegura la base SQLite en `database/database.sqlite`.
- Ejecuta migraciones y seeders (incluye usuario admin por defecto: `admin@domain.com` / `AdminPass123!`) vía `bun run be migrate:fresh:seed`.
- Genera rutas Ziggy para el frontend (`bun run be ziggy`).
- Limpia cachés de Laravel y ejecuta una limpieza final (`bun run be clear:all`).

Al final imprime cómo arrancar el proyecto con `bun dev`.

## Opción B: Setup manual (Local + SQLite)

> ```bash
> git clone https://github.com/gschz/foundry-stack.git
> cd foundry-stack
>
> bun run i:all
> cp .envs/.env.local.example .envs/.env.local
> ```
>
> Genera una `APP_KEY` y pégala en `.envs/.env.local`:
>
> ```bash
> php backend/artisan key:generate --show
> ```
>
> Luego:
>
> ```bash
> bun run be migrate:fresh:seed
> bun dev
> ```
>
> Acceso:
>
> - Frontend: http://localhost:5173
> - Backend: http://localhost:8080

## Artisan y variables de entorno

Para evitar ejecutar Artisan “a mano” (y perder el `--env-file` correcto), usa los scripts del proyecto:

- Local (SQLite): `bun run be artisan <comando>` (carga `.envs/.env.local`)
- PostgreSQL local: `bun run be pg <comando>` (carga `.envs/.env.pg.local`)
- Docker: `bun run dk:artisan <comando>` (ejecuta dentro del contenedor backend)

## PostgreSQL local

> 1. Crear el archivo de entorno:
>
> ```bash
> cp .envs/.env.pg.local.example .envs/.env.pg.local
> ```
>
> 2. Ajustar credenciales de DB en `.envs/.env.pg.local`.
> 3. Generar `APP_KEY` y pegarla en `.envs/.env.pg.local`:
>
> ```bash
> php backend/artisan key:generate --show
> ```
>
> 4. Migraciones y seeders:
>
> ```bash
> bun run be pg:migrate:fresh:seed
> ```
>
> 5. Iniciar desarrollo con PostgreSQL:
>
> ```bash
> bun run pg:dev
> ```

## Docker

> 1. Crear el archivo de entorno:
>
> ```bash
> cp .envs/.env.docker.example .envs/.env.docker
> ```
>
> 2. Generar `APP_KEY` y pegarla en `.envs/.env.docker`:
>
> ```bash
> php backend/artisan key:generate --show
> ```
>
> 3. Levantar contenedores + frontend (host):
>
> ```bash
> bun run dk:start
> ```
>
> 4. Migraciones y seeders:
>
> ```bash
> bun run dk:artisan migrate:fresh --seed
> ```
>
> Acceso:
>
> - Frontend: http://localhost:5173
> - Backend (Docker): http://localhost

## Verificación

- Backend: `bun run be qa`
- Frontend: `bun run fe lint` y `bun run fe types`

## Problemas comunes

> **Error: No application encryption key has been specified**

- Causa: `APP_KEY` faltante en el archivo de entorno activo.
- Solución: generar con `key:generate --show` y pegar la clave en `.envs/.env.*`.

> **El backend no levanta en 8080**

- Causa: puerto ocupado.
- Solución: libera el puerto o ajusta el puerto en `bun run be dev` (backend/package.json).

Lista completa de comandos: [COMMANDS.md](COMMANDS.md).

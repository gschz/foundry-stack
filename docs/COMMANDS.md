# Guía de Comandos Foundry Stack

> **Versión:** 2.1
> **Última Actualización:** 2026-01-28

Este proyecto utiliza **Bun** como gestor de paquetes _frontend_ y ejecutor de scripts principal. La estructura se basa en **Workspaces** (`backend`, `frontend` y `packages/*`) orquestados desde la raíz.

Todos los comandos se pueden ejecutar desde la raíz del proyecto utilizando: `bun run <script>`.

---

## 1. Comandos Globales (Raíz)

Scripts de orquestación y utilidad general definidos en `package.json` (raíz).

| Script   | Comando                                 | Descripción                                                                     |
| :------- | :-------------------------------------- | :------------------------------------------------------------------------------ |
| `i:all`  | `bun i && bun run be i && bun run fe i` | Instala todas las dependencias (raíz, backend y frontend).                      |
| `dev`    | `concurrently ...`                      | **Modo Desarrollo (SQLite)**: Inicia Backend, Colas y Frontend en paralelo.     |
| `pg:dev` | `concurrently ...`                      | **Modo Desarrollo (PostgreSQL)**: Igual que `dev` pero usando PostgreSQL local. |

---

## 2. Wrappers de Workspace (`be` y `fe`)

Para mantener la raíz limpia, usamos "wrappers" que delegan la ejecución a los scripts específicos de cada workspace.

- **`be`** → Ejecuta scripts del **Backend** (`backend/package.json`).
  - Uso: `bun run be <script>`
- **`fe`** → Ejecuta scripts del **Frontend** (`frontend/package.json`).
  - Uso: `bun run fe <script>`

---

## 3. Backend (Laravel)

Estos comandos se ejecutan vía `bun run be <script>`.
El backend maneja automáticamente la carga de variables de entorno (por defecto `.envs/.env.local`, o `.envs/.env.pg.local` para comandos `pg:`).

### Entorno Local (SQLite)

Usa `.envs/.env.local`. Ideal para desarrollo rápido.

| Script (`bun run be ...`) | Descripción                                                                  |
| :------------------------ | :--------------------------------------------------------------------------- |
| `i`                       | Instala dependencias de PHP vía Composer.                                    |
| `artisan`                 | Ejecuta comandos de Artisan. Ej: `bun run be artisan list`.                  |
| `dev`                     | Genera rutas Ziggy e inicia el servidor de desarrollo (`php artisan serve`). |
| `ql`                      | Inicia el worker de colas (`queue:listen`).                                  |
| `migrate:fresh:seed`      | Reinicia la BD y ejecuta todos los seeders.                                  |
| `tinker`                  | Abre la consola interactiva de Laravel (Tinker).                             |
| `qa`                      | Ejecuta suite de calidad: Pint, Tests (Pest), PHPStan y Rector.              |
| `dump`                    | Ejecuta `composer dump-autoload`.                                            |
| `clear:all`               | Limpia todas las cachés de Laravel y optimiza.                               |
| `make-module`             | Crea un nuevo módulo del proyecto.                                           |
| `ziggy`                   | Genera el archivo de rutas Ziggy para el frontend.                           |

### Entorno Local (PostgreSQL)

Usa `.envs/.env.pg.local`. Requiere un servidor PostgreSQL corriendo.

| Script (`bun run be ...`) | Descripción                                                  |
| :------------------------ | :----------------------------------------------------------- |
| `pg`                      | Alias de Artisan para PostgreSQL. Ej: `bun run be pg about`. |
| `pg:dev`                  | Inicia el servidor backend conectado a Postgres.             |
| `pg:ql`                   | Worker de colas para el entorno Postgres.                    |
| `pg:migrate:fresh:seed`   | Reinicia la BD y ejecuta seeders en Postgres.                |
| `pg:tinker`               | Tinker conectado a Postgres.                                 |

---

## 4. Scripts de Composer (`backend/composer.json`)

Estos scripts son internos de Composer y se utilizan para tareas de mantenimiento y calidad.
Se pueden ejecutar desde la raíz con `composer -d backend <script>`, o en el directorio `backend` con `composer run <script>`.

| Script       | Descripción                                        |
| :----------- | :------------------------------------------------- |
| `pint`       | Ejecuta Laravel Pint para formatear código.        |
| `pint:test`  | Verifica el formato del código con Pint (dry-run). |
| `test`       | Ejecuta la suite de pruebas con Pest.              |
| `test:types` | Análisis estático de tipos con PHPStan (Larastan). |
| `rector:dry` | Verifica refactor automático con Rector (dry-run). |
| `rector:fix` | Aplica refactor automático con Rector.             |

---

## 5. Frontend (Vite + React)

Estos comandos se ejecutan vía `bun run fe <script>`.

| Script (`bun run fe ...`) | Descripción                                       |
| :------------------------ | :------------------------------------------------ |
| `i`                       | Instala dependencias del frontend con Bun.        |
| `dev`                     | Inicia el servidor de desarrollo de Vite.         |
| `build`                   | Construye el frontend para producción.            |
| `preview`                 | Sirve la build de producción localmente.          |
| `lint`                    | Ejecuta ESLint.                                   |
| `lint:fix`                | Ejecuta ESLint y corrige errores automáticamente. |
| `format:check`            | Verifica formato con Prettier.                    |
| `format:write`            | Aplica formato con Prettier.                      |
| `types`                   | Verificación de tipos TypeScript (`tsc`).         |

---

## 6. Docker

Comandos para gestionar el entorno contenerizado. Se ejecutan desde la raíz.

| Script       | Descripción                                                     |
| :----------- | :-------------------------------------------------------------- |
| `dk`         | Alias para `docker-compose` con el archivo de entorno docker.   |
| `dk:start`   | Levanta el stack Docker y el frontend local conectado a él.     |
| `dk:up`      | Levanta contenedores en segundo plano (`-d`).                   |
| `dk:up:fg`   | Levanta contenedores en primer plano.                           |
| `dk:build`   | Reconstruye las imágenes de Docker.                             |
| `dk:down`    | Detiene y elimina contenedores.                                 |
| `dk:logs`    | Muestra logs de los contenedores en tiempo real.                |
| `dk:artisan` | Ejecuta Artisan _dentro_ del contenedor backend.                |
| `dk:sh`      | Abre una shell dentro del contenedor backend.                   |
| `dev:fe:dk`  | Inicia el frontend local configurado para el backend en Docker. |

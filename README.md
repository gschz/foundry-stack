# laravel-react-modular-stack

Base modular en Laravel 12 + React/TypeScript con Inertia y Vite. Incluye navegación contextual, filtrado de rutas con Ziggy, soporte opcional para PostgreSQL JSONB (compatible con SQLite en desarrollo), monorepo con linters/formatters, y orquestación de entornos locales y Docker/Nginx.

## Características clave

- Arquitectura modular en backend (Laravel) y frontend (React/TypeScript + Vite).
- Navegación contextual y renderizado dinámico en el frontend.
- Filtrado de rutas con Ziggy para exponer solo lo necesario al cliente.
- Soporte para PostgreSQL con JSONB.
- Monorepo con tooling unificado (Pint, Prettier, ESLint, PHPStan, Rector, Pest).

## Requisitos

- Bun 1.2+
- PHP 8.4+ y Composer
- PostgreSQL (opcional)
- Docker (opcional)
- Git

---

<details>
<summary>Entornos de Desarrollo</summary>

### Desarrollo Local (Sin Docker + SQLite)

Este modo es ideal para trabajar rápidamente en el frontend o en tareas del backend que no tienen dependencias complejas de infraestructura. Utiliza **SQLite** como base de datos.

**Configuración inicial:**

> **Crear archivo de entorno local**:
>
> ```bash
> cp .env.local.example .env.local
> ```
>
> **Instalar todas las dependencias** (backend, frontend y paquetes):
>
> ```bash
> bun run i:all
> ```
>
> **Configurar la base de datos y la aplicación**:
>
> ```bash
> bun run migrate:fresh:seed
> ```
>
> **¡Iniciar el entorno de desarrollo!**
>
> ```bash
> bun dev
> ```
>
> Iniciará el servidor de PHP y el servidor de desarrollo de Vite simultáneamente.

---

### Desarrollo Local con PostgreSQL

Este modo permite aprovechar las características avanzadas de PostgreSQL como JSONB, manteniendo la velocidad de desarrollo local.

**Configuración inicial:**

> **Crear archivo de entorno para PostgreSQL local**:
>
> ```bash
> cp .env.pg.local.example .env.pg.local
> ```
>
> **Asegúrate de tener PostgreSQL instalado localmente**
>
> **Configurar la base de datos y la aplicación**:
>
> ```bash
> bun run pg:migrate:fresh --seed
> ```
>
> **¡Iniciar el entorno de desarrollo con PostgreSQL!**
>
> ```bash
> bun pg:dev
> ```
>
> Iniciará el servidor de PHP conectado a PostgreSQL y el servidor de desarrollo de Vite simultáneamente.

---

### Desarrollo con Docker

Este modo proporciona un entorno consistente y aislado, más cercano a producción. Utiliza **PostgreSQL** y **Nginx** en contenedores.

**Configuración inicial:**

> **Crear archivo de entorno para Docker**:
>
> ```bash
> cp .env.docker.example .env.docker
> ```
>
> **Generar y configurar la `APP_KEY`**: Ejecuta el siguiente comando y copia la clave generada en tu archivo `.env.docker`:
>
> ```bash
> bun dk:artisan key:generate --show
> ```
>
> **¡Iniciar el entorno Docker!**
>
> ```bash
> bun run dk:start
> ```
>
> Este comando orquesta todo: levanta los contenedores de Docker (backend, db, nginx) y, al mismo tiempo, inicia el servidor de Vite en tu máquina local.
>
> **Ejecutar migraciones y seeders** (en una terminal separada):
>
> ```bash
> bun dk:artisan migrate:fresh --seed
> ```

</details>

---

<details>
<summary>Instalación y Configuración</summary>

### Instalación Rápida (Recomendada)

La forma más fácil de comenzar con el proyecto es usando el script de instalación automática:

> ```bash
> # 1. Clonar el repositorio
> git clone https://github.com/hkxdv/laravel-react-modular-stack.git my-project && cd my-project
> ||
> bunx degit hkxdv/laravel-react-modular-stack my-project && cd my-project
>
> # 2. Ejecutar instalación rápida
> bun run quick-install
> ```

Después de la instalación inicial, Bun seguirá siendo necesario para comandos de desarrollo (`bun dev`, seeds, Ziggy, etc.).

Este script realizará automáticamente:

- ✅ Verificación de la estructura del proyecto
- ✅ Creación de archivos de configuración (`.env.local`, `.env.users`)
- ✅ Instalación de dependencias del workspace, backend y frontend
- ✅ Configuración de base de datos SQLite
- ✅ Ejecución de migraciones y seeders
- ✅ Creación de usuarios del sistema
- ✅ Generación de rutas Ziggy
- ✅ Limpieza de cachés

**Al finalizar, podrás iniciar el proyecto con:**

```bash
bun dev
```

### Agregar Nuevos Usuarios

> Para agregar más usuarios al sistema después de la instalación inicial:
>
> 1. **Edita el archivo `.env.users`** siguiendo la convención de variables:
>
> ```bash
> USER_STAFF_{N}_NAME="Nombre"
> USER_STAFF_{N}_EMAIL="correo@dominio.com"
> USER_STAFF_{N}_PASSWORD="contraseña_segura"
> USER_STAFF_{N}_ROLE="ROL_OPCIONAL"  # ej. ADMIN, DEV, MOD-01
> ```
>
> 2. **Ejecuta el seeder de usuarios:**
>
> ```bash
> # Para Local/SQLite
> bun run seed:users
>
> # Para PostgreSQL
> bun run pg:seed:users
> ```

### Estructura de Archivos de Configuración

El proyecto utiliza diferentes archivos de entorno según el modo de desarrollo:

- **`.env.local`** - Desarrollo local con SQLite (creado automáticamente)
- **`.env.users`** - Credenciales de usuarios (creado durante la instalación)
- **`.env.pg.local.example`** - Plantilla para PostgreSQL local
- **`.env.docker.example`** - Plantilla para entorno Docker

### Solución de Problemas

> **Regenerar archivos de configuración:**
>
> ```bash
> # Si necesitas recrear los archivos de entorno
> rm .env.local .env.users
> bun run quick-install
> ```
>
> **Limpiar instalación:**
>
> ```bash
> # Limpiar cachés y dependencias
> bun run clear:all
> rm -rf node_modules backend/vendor frontend/node_modules
> bun run quick-install
> ```

</details>

---

<details>
<summary>Configuración técnica (resumen)</summary>

## Configuración técnica (resumen)

- Monorepo y scripts (package.json raíz)
  - Workspaces: frontend, backend
  - Gestor de scripts: Bun; utilidades: concurrently, dotenv-cli
  - Scripts clave:
    - dev: orquesta backend (php artisan serve en :8080) + queue:listen + Vite
    - ziggy / pg:ziggy: genera frontend/src/ziggy.js desde rutas de Laravel según el entorno
    - Entornos: .env.local (local/SQLite), .env.pg.local (PostgreSQL local), .env.docker (Docker)
    - Docker: dk:start, dk:up, dk:down, dk:logs, dk:artisan, dev:fe:docker

- Frontend (frontend/package.json)
  - Tech stack: React 19, Vite 6, TypeScript 5, Tailwind 4, Inertia React 2, Ziggy JS 2.5, TanStack Query 5
  - Utilidades: laravel-vite-plugin, date-fns, lucide-react, shadcn/ui (ecosistema)

- Vite (frontend/vite.config.ts)
  - envDir: "../" (lee variables del raíz)
  - Server: host dinámico (localhost en local, 0.0.0.0 en prod/Docker), puerto 5173, watch con polling
  - HMR: toma el host de VITE_APP_URL (hostname) cuando APP_RUNNING_IN_CONTAINER=true o en producción
  - Plugins: laravel-vite-plugin (input: src/app.tsx; publicDirectory: ../backend/public; refresh: true), @vitejs/plugin-react, @tailwindcss/vite
  - Alias: @ -> frontend/src

- ESLint (frontend/eslint.config.js)
  - Configuración flat con typescript-eslint (recommended + stylistic)
  - Plugins: react, react-hooks, jsx-a11y, import, unicorn, sonarjs
  - Reglas destacadas: no-explicit-any (error), consistent-type-imports, no-unused-vars con patrones, reglas de calidad (unicorn/sonarjs), integración Prettier

- Backend (backend/composer.json)
  - Framework: Laravel 12 + Inertia Laravel 2, Sanctum, Scout
  - Modularidad: nwidart/laravel-modules
  - Autorización y registro: spatie/laravel-permission, spatie/laravel-activitylog
  - Datos/DTO: spatie/laravel-data
  - Ruteo cliente: tightenco/ziggy
  - Dev: laravel/telescope, laravel/pint, laravel/pail, phpunit

- Variables de entorno clave
  - VITE_APP_URL: base para HMR (hostname)
  - APP_RUNNING_IN_CONTAINER=true: activa host/HMR para Docker/LAN
  - Servidor backend local: php artisan serve --host=localhost --port=8080 (controlado por scripts)
  - Selección de DB: .env.local (SQLite) vs .env.pg.local (PostgreSQL); scripts pg:\* usan el segundo

- Tips de uso
  - Genera Ziggy después de añadir/modificar rutas: bun run ziggy (o bun run pg:ziggy)
  - Limpieza de cachés/autoload: bun run clear:all
  - Chequeos rápidos: bun run lint, bun run types, composer pint:test

</details>

---

## Créditos y referencia

Este proyecto está basado y toma como punto de partida el React Starter Kit oficial de Laravel. Todo crédito para Laravel y su comunidad.

Documentación oficial: https://laravel.com/docs/12.x/starter-kits

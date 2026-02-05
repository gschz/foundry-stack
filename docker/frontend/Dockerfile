# ====================================================================
# Dockerfile para Frontend con Bun (LEGACY)
# ====================================================================
# - NOTA: Esta configuración se mantiene como referencia pero
#   actualmente no está en uso activo (véase docker-compose.override.yml)
# ====================================================================

FROM oven/bun:latest

WORKDIR /app

# Copia archivos de configuración de paquetes
COPY frontend/package.json frontend/bun.lock* frontend/tsconfig.json ./frontend/

# Cambia al directorio frontend para instalar dependencias
WORKDIR /app/frontend

# Instala dependencias con lockfile para garantizar consistencia
RUN bun install --frozen-lockfile

# Copia el resto del código fuente de la aplicación frontend
COPY frontend/ .

# Expone puerto que usa Vite para desarrollo
EXPOSE 5173

# Inicia el servidor de desarrollo
# El script "dev" en package.json debe incluir la bandera --host
# para permitir acceso desde fuera del contenedor
CMD ["bun", "run", "dev"]

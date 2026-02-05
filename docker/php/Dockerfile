# ====================================================================
# Dockerfile para PHP-FPM con soporte para PostgreSQL
# ====================================================================

FROM php:8.4-fpm

# Instala dependencias del sistema y las necesarias para health-check.
# Limpia todo en una sola capa para reducir el tamaño de la imagen
RUN apt-get update && apt-get install -y \
    ca-certificates \
    curl \
    git \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpng-dev \
    libzip-dev \
    libfcgi-bin \
    libpq-dev \
    --no-install-recommends && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Instala extensiones de PHP
RUN docker-php-ext-install mbstring exif pcntl bcmath gd zip pdo_pgsql

# Obtiene la última versión de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establece el directorio de trabajo
WORKDIR /var/www

# Crea la estructura de directorios para la aplicación y configura permisos iniciales
RUN mkdir -p backend/storage/framework/{sessions,views,cache} \
    backend/bootstrap/cache \
    database && \
    chown -R www-data:www-data /var/www

# Cambia al usuario no-root para todas las operaciones subsecuentes
USER www-data

# Copia los archivos de composer e instala dependencias de la aplicación
COPY --chown=www-data:www-data backend/composer.json backend/composer.lock* ./backend/
RUN composer install --working-dir=./backend --optimize-autoloader --no-dev --no-interaction --prefer-dist --no-scripts

# Copia el resto de los archivos de la aplicación
COPY --chown=www-data:www-data backend/ ./backend/
COPY --chown=www-data:www-data database/ ./database/

# Ejecuta los scripts de composer ahora que el código de la aplicación está presente
RUN composer run-script --working-dir=./backend post-autoload-dump --no-dev --no-interaction

# Volve al usuario root para configurar permisos finales
USER root

# Configura permisos para almacenamiento y caché de Laravel
RUN chown -R www-data:www-data /var/www/backend/storage /var/www/backend/bootstrap/cache && \
    chmod -R 775 /var/www/backend/storage /var/www/backend/bootstrap/cache

# Healthcheck para asegurar que el proceso FPM responde
RUN sed -ri 's~^;?ping.path\s*=.*$~ping.path = /ping~' /usr/local/etc/php-fpm.d/www.conf

HEALTHCHECK --interval=15s --timeout=5s --start-period=10s --retries=5 CMD ["sh","-c","cgi-fcgi -connect 127.0.0.1:9000 /ping || exit 1"]

# Volve al usuario no-root para ejecutar el proceso de la aplicación
USER www-data

# Expone puerto y ejecuta php-fpm
EXPOSE 9000
CMD ["php-fpm"]

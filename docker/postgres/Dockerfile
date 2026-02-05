# ====================================================================
# Dockerfile para PostgreSQL
# ====================================================================

FROM postgres:17-alpine

# Actualiza todos los paquetes para aplicar parches de seguridad
RUN apk update && \
    apk upgrade --no-cache && \
    # Instala herramientas de seguridad básicas
    apk add --no-cache \
    tzdata \
    ca-certificates && \
    # Limpia la caché de APK para reducir el tamaño de la imagen
    rm -rf /var/cache/apk/*

# Configuramos opciones de seguridad para PostgreSQL
RUN echo "host all all 0.0.0.0/0 scram-sha-256" >> /usr/local/share/postgresql/pg_hba.conf.sample && \
    echo "password_encryption = scram-sha-256" >> /usr/local/share/postgresql/postgresql.conf.sample

# Scripts de inicialización (se ejecutan sólo en la primera creación del cluster)
COPY docker/postgres/init-scripts /docker-entrypoint-initdb.d

# Configura permisos restrictivos para los directorios de datos
RUN mkdir -p /var/lib/postgresql/data && \
    chmod 700 /var/lib/postgresql/data

# Expone puerto por defecto de PostgreSQL
EXPOSE 5432
# ====================================================================
# Dockerfile para pgAdmin (entorno de desarrollo)
# ====================================================================

FROM dpage/pgadmin4:latest

# Instala utilidades básicas de forma compatible con Alpine/Debian
USER root
RUN set -e; \
  if command -v apk >/dev/null 2>&1; then \
  apk add --no-cache curl tzdata; \
  else \
  apt-get update && apt-get install -y --no-install-recommends curl tzdata && \
  rm -rf /var/lib/apt/lists/*; \
  fi

# Volve al usuario por defecto de la imagen
USER pgadmin

# Expone puerto por defecto de pgAdmin
EXPOSE 80
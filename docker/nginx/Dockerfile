# ====================================================================
# Dockerfile para Nginx
# ====================================================================

FROM nginx:1.28.0-alpine-slim

# Instalamos dependencias necesarias y aplicamos configuraciones en una sola capa
RUN apk add --no-cache curl tzdata && \
  mkdir -p /var/cache/nginx /etc/nginx/snippets && \
  # Configuración de seguridad y rendimiento en nginx.conf
  sed -i 's/access_log.*/access_log \/var\/log\/nginx\/access.log combined buffer=512k flush=1m;/' /etc/nginx/nginx.conf && \
  sed -i 's/error_log.*/error_log \/var\/log\/nginx\/error.log warn;/' /etc/nginx/nginx.conf && \
  sed -i 's/worker_connections.*/worker_connections 2048;/' /etc/nginx/nginx.conf && \
  sed -i 's/keepalive_timeout.*/keepalive_timeout 65;/' /etc/nginx/nginx.conf && \
  sed -i 's/sendfile.*/sendfile on;\n\ttcp_nodelay on;\n\ttcp_nopush on;/' /etc/nginx/nginx.conf && \
  # Ejecutar como no-root: comenta directiva 'user' y usa PID escribible
  sed -ri 's/^(user\s+.+;)/# \1/' /etc/nginx/nginx.conf && \
  if grep -qE '^pid\s+' /etc/nginx/nginx.conf; then \
  sed -ri 's#^pid\s+.*;#pid /var/cache/nginx/nginx.pid;#' /etc/nginx/nginx.conf; \
  else \
  sed -i '1i pid /var/cache/nginx/nginx.pid;' /etc/nginx/nginx.conf; \
  fi && \
  # Ocultar versión de Nginx y endurecer buffers
  echo 'server_tokens off;' > /etc/nginx/conf.d/security.conf && \
  echo 'client_body_buffer_size 10K;' >> /etc/nginx/conf.d/security.conf && \
  echo 'client_header_buffer_size 1k;' >> /etc/nginx/conf.d/security.conf && \
  echo 'client_max_body_size 20m;' >> /etc/nginx/conf.d/security.conf && \
  echo 'large_client_header_buffers 2 1k;' >> /etc/nginx/conf.d/security.conf && \
  # Permisos de directorios
  mkdir -p /var/run/nginx && \
  chown -R nginx:nginx /var/cache/nginx /etc/nginx/conf.d /var/log/nginx /var/run/nginx && \
  chmod -R 755 /var/cache/nginx

# Copiamos archivos de configuración
COPY default.conf /etc/nginx/conf.d/default.conf
COPY snippets/protect-assets.conf /etc/nginx/snippets/protect-assets.conf

# Ejecutar como usuario no-root
USER nginx

# Añadimos healthcheck para asegurar que Nginx responde correctamente
HEALTHCHECK --interval=10s --timeout=5s --start-period=5s --retries=3 \
  CMD curl -f http://localhost:8080/ping || exit 1

# Exponemos puerto no privilegiado para ejecutar como usuario no-root
EXPOSE 8080

# Ejecutamos Nginx en primer plano
CMD ["nginx", "-g", "daemon off;"]
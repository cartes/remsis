#!/bin/sh
# Arranque del contenedor de Remsis.
#
# Variables opcionales:
#   RUN_MIGRATIONS=true   ejecuta `php artisan migrate --force` antes de servir (por defecto no).
#   RUN_SCHEDULER=false   no levanta el programador de tareas (por defecto sí).
#   RUN_QUEUE=true        levanta un worker de colas en el mismo contenedor (por defecto no).
set -e

cd /app

if [ -z "$APP_KEY" ]; then
    echo "Falta APP_KEY: usa la misma clave del entorno actual o las sesiones y datos cifrados dejarán de leerse." >&2
    exit 1
fi

# El disco público (fotos de perfil) se sirve desde public/storage.
php artisan storage:link --force >/dev/null 2>&1 || true

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

# La configuración se cachea en el arranque, cuando ya existen las variables del entorno.
php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "${RUN_SCHEDULER:-true}" = "true" ]; then
    php artisan schedule:work &
fi

if [ "${RUN_QUEUE:-false}" = "true" ]; then
    php artisan queue:work --tries=3 --max-time=3600 &
fi

# Railway entrega el puerto en $PORT y termina el TLS en su proxy: se sirve HTTP plano.
export SERVER_NAME=":${PORT:-8080}"

exec frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile

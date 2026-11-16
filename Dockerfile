# syntax=docker/dockerfile:1

# ── 1. Recursos del front (Vite + Tailwind + UnoCSS) ─────────────────────────
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
# Tailwind y UnoCSS leen las vistas de todos los módulos para generar el CSS.
COPY . .
RUN npm run build

# ── 2. Aplicación (PHP 8.5 con servidor web FrankenPHP) ──────────────────────
FROM dunglas/frankenphp:1-php8.5-bookworm AS app

# pdo_mysql y pdo_pgsql: la imagen sirve para la base que ya use el entorno (DB_CONNECTION).
# intl: monto en palabras de la liquidación. gd: imágenes en los PDF.
RUN install-php-extensions pdo_mysql pdo_pgsql intl gd zip bcmath opcache pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Dependencias primero: esta capa se reutiliza mientras no cambie composer.lock.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && chmod +x docker/entrypoint.sh \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/public bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-remsis.ini"

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=8080

EXPOSE 8080

ENTRYPOINT ["docker/entrypoint.sh"]

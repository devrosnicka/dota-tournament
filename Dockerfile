# syntax=docker/dockerfile:1

FROM dunglas/frankenphp:1-php8.4-trixie AS base

RUN install-php-extensions pdo_sqlite intl zip opcache pcntl bcmath \
    && apt-get update \
    && apt-get install -y --no-install-recommends sqlite3 \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Development: PHP + Composer + Node, source code is bind-mounted into /app.
FROM base AS dev

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=node:24-trixie-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-trixie-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Writable locations for an arbitrary (host) UID.
ENV XDG_CONFIG_HOME=/tmp/xdg/config \
    XDG_DATA_HOME=/tmp/xdg/data \
    COMPOSER_HOME=/tmp/composer \
    npm_config_cache=/tmp/npm

# Build: production dependencies and compiled assets. The Vite build needs PHP
# as well, because Wayfinder generates the typed routes through artisan.
FROM dev AS build

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY package.json package-lock.json .npmrc ./
RUN npm ci

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && npm run build \
    && rm -rf node_modules

# Production: FrankenPHP serves the app on :8080 as an unprivileged user,
# TLS is terminated by caddy-docker-proxy in front of it.
FROM base AS prod

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && useradd --uid 1000 --user-group --no-create-home app \
    && chown -R app:app /config/caddy /data/caddy

COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/app-entrypoint
COPY --from=build --chown=app:app /app /app

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_DATABASE=/app/storage/database/database.sqlite \
    SESSION_SECURE_COOKIE=true \
    SERVER_NAME=:8080

USER app
EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s \
    CMD php -r 'exit(@file_get_contents("http://127.0.0.1:8080/up") === false ? 1 : 0);'

ENTRYPOINT ["app-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]

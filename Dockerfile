# syntax=docker/dockerfile:1.7


# ════════════════════════════════════════════════════════════════
# Stage 2 — PHP runtime (Apache)
# ════════════════════════════════════════════════════════════════
FROM php:8.4-apache AS app

# ────────────────────────────────────────────────────────────────
# System dependencies + PHP extensions
# ────────────────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        curl \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        intl \
        opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# ────────────────────────────────────────────────────────────────
# OPcache — production-ready defaults
# ────────────────────────────────────────────────────────────────
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=0'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.memory_consumption=256'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# ────────────────────────────────────────────────────────────────
# Composer binary
# ────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ────────────────────────────────────────────────────────────────
# PHP dependencies — cached layer, invalidated only when the
# composer manifests change.
# ────────────────────────────────────────────────────────────────
COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-interaction \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader \
        --no-progress

# ────────────────────────────────────────────────────────────────
# Application source (excluding what .dockerignore filters out)
# ────────────────────────────────────────────────────────────────
COPY . .

# ────────────────────────────────────────────────────────────────
# Regenerate the Composer autoloader against the full source tree
# so package:discover can see every provider.
# ────────────────────────────────────────────────────────────────
RUN composer dump-autoload --optimize --no-dev

# ────────────────────────────────────────────────────────────────
# Apache — point DocumentRoot at /public
# ────────────────────────────────────────────────────────────────
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/sites-available/000-default.conf \
        /etc/apache2/apache2.conf

RUN printf '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

# ────────────────────────────────────────────────────────────────
# Permissions + storage symlink
# ────────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data storage bootstrap/cache \
    && php artisan storage:link || true

# ────────────────────────────────────────────────────────────────
# Healthcheck — Laravel's /up endpoint
# ────────────────────────────────────────────────────────────────
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://localhost/up || exit 1

EXPOSE 80

CMD ["apache2-foreground"]

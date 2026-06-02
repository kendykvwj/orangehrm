# Dev image: builds OrangeHRM from THIS repo source (not the packaged release).
# Stage 1 builds the Vue frontends, stage 2 is the PHP/Apache runtime.

# ---------- Stage 1: frontend build ----------
FROM node:18-bookworm AS frontend
WORKDIR /app
COPY . .
RUN cd src/client && yarn install --frozen-lockfile && yarn build
RUN cd installer/client && yarn install --frozen-lockfile && yarn build

# ---------- Stage 2: PHP / Apache runtime ----------
FROM php:8.3-apache-bookworm

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# PHP extensions required by OrangeHRM (mirrors the release Dockerfile).
RUN set -ex; \
    savedAptMark="$(apt-mark showmanual)"; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libfreetype6-dev libjpeg-dev libpng-dev libzip-dev \
        libldap2-dev libicu-dev unzip git default-mysql-client; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-configure ldap --with-libdir=lib/$(uname -m)-linux-gnu/; \
    docker-php-ext-install -j "$(nproc)" gd opcache intl pdo_mysql zip ldap; \
    apt-mark auto '.*' > /dev/null; \
    apt-mark manual $savedAptMark default-mysql-client git; \
    ldd "$(php -r 'echo ini_get("extension_dir");')"/*.so \
        | awk '/=>/ { so = $(NF-1); if (index(so, "/usr/local/") == 1) { next }; gsub("^/(usr/)?", "", so); print so }' \
        | sort -u | xargs -r dpkg-query -S | cut -d: -f1 | sort -u \
        | xargs -rt apt-mark manual; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/cache/apt/archives /var/lib/apt/lists/*

RUN { \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.revalidate_freq=0'; \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini; \
    a2enmod rewrite

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Bring in source + built frontends from stage 1.
COPY --from=frontend /app /var/www/html

# PHP dependencies (app + dev tools used by the installer/console).
RUN cd src && composer install --no-interaction --prefer-dist --no-progress \
    && composer dump-autoload --optimize \
 && cd ../devTools/core && composer install --no-interaction --prefer-dist --no-progress

RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 775 lib/confs src/cache src/log src/config

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]

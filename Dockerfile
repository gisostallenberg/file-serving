FROM php:8.5-cli

# libonig-dev is needed to build the mbstring extension that PHPUnit requires;
# unzip lets Composer extract packages from zips instead of the git fallback.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev unzip \
    && docker-php-ext-install mbstring \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

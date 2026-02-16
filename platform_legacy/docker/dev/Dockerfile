FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libonig-dev \
        libsqlite3-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libcurl4-openssl-dev \
        libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        bcmath \
        curl \
        gd \
        intl \
        mbstring \
        pcntl \
        pdo \
        pdo_mysql \
        pdo_sqlite \
        xml \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY scripts/start-legacy.sh /usr/local/bin/start-legacy
RUN chmod +x /usr/local/bin/start-legacy

EXPOSE 8080

ENTRYPOINT ["start-legacy"]


# syntax=docker/dockerfile:1.4
FROM php:8.3-cli as php
WORKDIR /app
ENV PATH "/app/bin:/app/vendor/bin:/app/build/composer/vendor/bin:$PATH"
ENV COMPOSER_HOME "/app/build/composer"

RUN <<-EOF
  groupadd --gid 1000 dev;
  useradd --system --create-home --uid 1000 --gid 1000 --shell /bin/bash dev;
EOF

RUN <<-EOF
  apt-get update;
  apt-get install -y \
    apt-transport-https \
    autoconf  \
    build-essential \
    curl \
    git \
    less \
    libgmp-dev \
    libicu-dev \
    libzip-dev \
    libsodium-dev \
    pkg-config \
    unzip \
    vim-tiny \
    zip \
    zlib1g-dev;
  apt-get clean;
EOF

RUN <<-EOF
  docker-php-ext-install -j$(nproc) bcmath gmp intl opcache zip;
  pecl install xdebug;
  docker-php-ext-enable xdebug;
EOF

COPY --link --from=composer/composer:latest-bin /composer /usr/bin/composer
COPY --link settings.ini /usr/local/etc/php/conf.d/settings.ini

USER dev

FROM php:7.4.10-cli

RUN apt-get update && apt-get install -y \
    apt-utils \
    gnupg \
    lsb-release \
#        g++ \
    wget \
#        ssh \
#        unzip \
    libbz2-dev \
    libgmp-dev \
    libicu-dev \
    libmemcached-dev \
    libzip-dev \
    libpq-dev \
    zip

RUN wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add - \
    && echo "deb http://apt.postgresql.org/pub/repos/apt/ `lsb_release -cs`-pgdg main" |tee  /etc/apt/sources.list.d/pgdg.list

RUN apt-get update

RUN apt-get install -y \
    git

#RUN apt-get install -y \
#    libcurl4-gnutls-dev \
#    libfreetype6-dev \
#    libjpeg-dev \
#    libjpeg62-turbo-dev \
#    libpcre++-dev \
#    libpng-dev \
#    libxpm-dev \
#    libxml2-dev \
#    zlib1g-dev

# Base PHP Extensions
RUN docker-php-ext-install -j$(nproc) bcmath \
    && docker-php-ext-install -j$(nproc) bz2 \
#    && docker-php-ext-install -j$(nproc) exif \
    && docker-php-ext-install -j$(nproc) gmp \
    && docker-php-ext-install -j$(nproc) intl \
#    && docker-php-ext-install -j$(nproc) opcache \
#    && docker-php-ext-install -j$(nproc) soap \
#    && docker-php-ext-install -j$(nproc) sockets \
    && docker-php-ext-install -j$(nproc) zip

RUN pecl install apcu && docker-php-ext-enable apcu
#RUN pecl install dbase && docker-php-ext-enable dbase
#RUN pecl install memcached && docker-php-ext-enable memcached
#RUN pecl install redis && docker-php-ext-enable redis
#RUN pecl install timezonedb && docker-php-ext-enable timezonedb

### MariaDB
#RUN apt-get install -y mariadb-client \
#    && docker-php-ext-install -j$(nproc) mysqli \
#    && docker-php-ext-install -j$(nproc) pdo_mysql

### PostgreSQL
RUN apt-get install -y postgresql-client \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install -j$(nproc) pgsql \
    && docker-php-ext-install -j$(nproc) pdo_pgsql

### GD
#RUN docker-php-ext-configure gd \
#    --with-freetype=/usr/include/ \
#    --with-jpeg=/usr/include/ \
#    --with-xpm=/usr/lib/x86_64-linux-gnu \
#    && docker-php-ext-install -j$(nproc) gd

### ImageMagick
#RUN apt-get install -y imagemagick libmagickcore-dev libmagickwand-dev \
#    && pecl install imagick \
#    && docker-php-ext-enable imagick

### OAuth
#RUN pecl install oauth && docker-php-ext-enable oauth

### SSH2
#RUN apt-get install -y libssh2-1 libssh2-1-dev libssl-dev
#RUN wget -O libssh2.tar.gz https://www.libssh2.org/download/libssh2-1.9.0.tar.gz \
#    && wget -O ssh2.tgz https://pecl.php.net/get/ssh2-1.2.tgz \
#    && mkdir libssh2 && tar vxzf libssh2.tar.gz -C libssh2 --strip-components 1 \
#    && mkdir ssh2 && tar vxzf ssh2.tgz -C ssh2 --strip-components 1 \
#    && cd libssh2 && ./configure \
#    && make && make install \
#    && cd ../ssh2 && phpize && ./configure --with-ssh2 \
#    && make && make install \
#    && echo "extension=ssh2.so" >> /usr/local/etc/php/conf.d/ssh2.ini \
#    && cd ../ && rm -rf libssh2.tar.gz ssh2.tgz ssh2 libssh2

### AMQP
#RUN apt-get install librabbitmq-dev -y \
#    && pecl install amqp \
#    && docker-php-ext-enable amqp

# Custom soft
#RUN apt-get install -y sshpass hping3

### xdebug
#RUN pecl install xdebug && docker-php-ext-enable xdebug
#COPY ./php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Cleanups
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/bin --filename=composer --quiet
ENV COMPOSER_ALLOW_SUPERUSER 1

#WORKDIR /app

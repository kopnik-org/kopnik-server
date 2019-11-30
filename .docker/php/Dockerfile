FROM php:7.3-fpm
RUN  apt-get update \
     && apt-get install -y \
     libicu-dev zlib1g-dev g++ libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
     && docker-php-ext-install -j$(nproc) gd intl sockets zip pdo_mysql
RUN  curl --silent --show-error https://getcomposer.org/installer | php
RUN  mv composer.phar /usr/local/bin/composer

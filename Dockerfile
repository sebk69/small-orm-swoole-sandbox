FROM php:8.1-cli

# args
ARG work_user=www-data
ARG uid
ARG timezone

# install swoole
RUN pecl install swoole
RUN docker-php-ext-enable swoole

# install redis
RUN pecl install redis
RUN docker-php-ext-enable redis

# install mysql
RUN docker-php-ext-install pdo pdo_mysql

# No memory limit
RUN cd /usr/local/etc/php/conf.d/ && echo 'memory_limit = 500M' >> /usr/local/etc/php/conf.d/docker-php-ram-limit.ini

# Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=bin --filename=composer

# Set timezone
RUN cp /usr/share/zoneinfo/$timezone /etc/localtime \
    && echo "$timezone" > /etc/timezone \
    && echo "[Date]\ndate.timezone=$timezone" > /usr/local/etc/php/conf.d/timezone.ini

# Install packages
RUN apt-get update && apt-get install -y sudo wget git && rm -rf /var/lib/apt/lists/*

# Setup app
RUN chown www-data:www-data /var/www
WORKDIR /usr/src/app
RUN usermod -u $uid www-data
USER www-data
RUN wget https://get.symfony.com/cli/installer -O - | bash

ENTRYPOINT sleep infinity
FROM php:8.2.4-apache-bullseye

LABEL maintainer="Pere Orga pere@orga.cat"

ARG ARG_MYSQL_DB
ARG ARG_MYSQL_PWD
ARG ARG_MYSQL_USER
ARG ARG_WEB_ADMIN_PWD

ENV MYSQL_DATABASE=${ARG_MYSQL_DB}
ENV MYSQL_PASSWORD=${ARG_MYSQL_PWD}
ENV MYSQL_USER=${ARG_MYSQL_USER}
ENV WEB_ADMIN_PASSWORD=${ARG_WEB_ADMIN_PWD}

COPY . /srv/app

WORKDIR /srv/app

# Apache settings
COPY .docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
RUN rm -f /etc/apache2/mods-enabled/deflate.conf && \
    sed -i 's/ServerTokens OS/ServerTokens Prod/g' /etc/apache2/conf-available/security.conf

# PHP settings
RUN sed 's/expose_php = On/expose_php = Off/g' /usr/local/etc/php/php.ini-production > /usr/local/etc/php/php.ini

# PHP extensions
RUN docker-php-ext-install pdo_mysql opcache && pecl install apcu-5.1.22 && docker-php-ext-enable apcu
COPY .docker/php/apcu.ini /usr/local/etc/php/conf.d/apcu.ini

# apcu.php uses gd, but we usually don't care
#RUN apt-get update -y && apt-get install --no-install-recommends -y libpng-dev && apt-get clean && rm -rf /var/lib/apt/lists/* && docker-php-ext-install gd

# Enable extreme opcache optimizations (prod only)
COPY .docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Apache mods
RUN a2enmod rewrite && a2enmod headers && a2enmod brotli

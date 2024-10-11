FROM php:8.3.12-apache-bookworm
LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="Debian-based image with Apache and mod_php. This was used in production prior to having Alpine."

ARG ARG_MYSQL_DB
ARG ARG_MYSQL_PWD
ARG ARG_MYSQL_USER
ARG ARG_WEB_ADMIN_PWD

ENV MYSQL_DATABASE=${ARG_MYSQL_DB}
ENV MYSQL_PASSWORD=${ARG_MYSQL_PWD}
ENV MYSQL_USER=${ARG_MYSQL_USER}
ENV WEB_ADMIN_PASSWORD=${ARG_WEB_ADMIN_PWD}

ENV DEBIAN_FRONTEND=noninteractive

# Set working directory
WORKDIR /srv/app

# Install install-php-extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Update package list and upgrade packages
RUN apt-get update && apt-get upgrade -y && apt-get clean && rm -rf /var/lib/apt/lists/*

# Remove some Apache default settings provided by Debian
# Do not expose Apache version in header
# Enable Apache modules
# Use PHP default production settings, but do not expose PHP version
# Install PHP extensions
RUN rm -f /etc/apache2/mods-enabled/deflate.conf /etc/apache2/mods-enabled/alias.conf && \
    sed -i 's/ServerTokens OS/ServerTokens Prod/g' /etc/apache2/conf-available/security.conf && \
    a2enmod rewrite headers brotli && \
    sed 's/expose_php = On/expose_php = Off/g' /usr/local/etc/php/php.ini-production > /usr/local/etc/php/php.ini && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions apcu gd opcache pdo_mysql

# Copy configuration files
COPY .docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/php/performance.ini /usr/local/etc/php/conf.d/performance.ini

# Copy project files
COPY docroot ./docroot
COPY scripts ./scripts
COPY src ./src
COPY tmp ./tmp

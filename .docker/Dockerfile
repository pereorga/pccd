FROM php:8.2.14-apache-bookworm

LABEL maintainer="Pere Orga pere@orga.cat"

ARG profiler

# Set working directory
WORKDIR /srv/app

# Install install-php-extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Copy configuration files
COPY .docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/php/apcu.ini /usr/local/etc/php/conf.d/apcu.ini

# Apache/PHP Configuration
# - Remove Apache Deflate and Alias default settings provided by Debian
# - Set PHP production settings
# - DEV only: enable PHP assertions
# - Install essential PHP extensions
# - Disable unnecessary Apache modules
# - Enable required Apache modules for URL rewriting, HTTP headers manipulation, and Brotli compression
RUN rm -f /etc/apache2/mods-enabled/deflate.conf /etc/apache2/mods-enabled/alias.conf && \
    cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini && \
    sed -i 's/zend.assertions = -1/zend.assertions = 1/g' /usr/local/etc/php/php.ini && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions apcu intl opcache pdo_mysql && \
    a2dismod -f access_compat auth_basic authn_core authn_file authz_host authz_user autoindex negotiation setenvif status && \
    a2enmod rewrite headers brotli

# apcu.php uses gd, but we usually don't care
#RUN install-php-extensions gd

# Copy project files
COPY . .

# SPX profiler
RUN if [ "$profiler" = "spx" ]; then \
        cp .docker/php/spx.ini /usr/local/etc/php/conf.d/spx.ini && \
        install-php-extensions spx; \
    fi

# XHProf profiler
RUN if [ "$profiler" = "xhprof" ]; then \
        install-php-extensions xhprof && \
        grep -h -F -v '</VirtualHost>' .docker/apache/vhost.conf > /etc/apache2/sites-available/000-default.conf && \
        cat .docker/apache/xhprof.conf >> /etc/apache2/sites-available/000-default.conf && \
        echo '</VirtualHost>' >> /etc/apache2/sites-available/000-default.conf && \
        cp .docker/php/xhprof.ini /usr/local/etc/php/conf.d/xhprof.ini; \
    fi

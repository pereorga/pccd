FROM php:8.2.13-apache-bookworm

LABEL maintainer="Pere Orga pere@orga.cat"

ARG profiler

# Set working directory
WORKDIR /srv/app

# Copy configuration files
COPY .docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/php/apcu.ini /usr/local/etc/php/conf.d/apcu.ini

# Set Apache/PHP settings and enable PHP extensions and Apache modules, including enabling PHP assertions (DEV only)
RUN apt-get update -y && \
    apt-get install --no-install-recommends -y libicu-dev zlib1g-dev && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    rm -f /etc/apache2/mods-enabled/deflate.conf && \
    cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini && \
    sed -i 's/zend.assertions = -1/zend.assertions = 1/g' /usr/local/etc/php/php.ini && \
    docker-php-ext-install pdo_mysql opcache intl && \
    pecl install apcu-5.1.23 && \
    docker-php-ext-enable apcu && \
    a2enmod rewrite headers brotli

# apcu.php uses gd, but we usually don't care
#RUN apt-get update -y && apt-get install --no-install-recommends -y libpng-dev && apt-get clean && rm -rf /var/lib/apt/lists/* && docker-php-ext-install gd

# Copy project files
COPY . .

# SPX profiler
RUN if [ "$profiler" = "spx" ]; then \
        mkdir -p /usr/src/php/ext/ && \
        cp -a vendor/noisebynorthwest/php-spx /usr/src/php/ext/spx && \
        docker-php-ext-install spx && \
        cp .docker/php/spx.ini /usr/local/etc/php/conf.d/spx.ini; \
    fi

# XHProf profiler
RUN if [ "$profiler" = "xhprof" ]; then \
        apt-get update -y && \
        apt-get install --no-install-recommends -y graphviz && \
        apt-get clean && \
        rm -rf /var/lib/apt/lists/* && \
        pecl install xhprof-2.3.9 && \
        docker-php-ext-enable xhprof && \
        cp .docker/php/xhprof.ini /usr/local/etc/php/conf.d/xhprof.ini && \
        grep -h -F -v '</VirtualHost>' .docker/apache/vhost.conf > /etc/apache2/sites-available/000-default.conf && \
        cat .docker/apache/xhprof.conf >> /etc/apache2/sites-available/000-default.conf && \
        echo '</VirtualHost>' >> /etc/apache2/sites-available/000-default.conf; \
    fi

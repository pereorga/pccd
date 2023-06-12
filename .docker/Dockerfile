FROM php:8.2.7-apache-bullseye

LABEL maintainer="Pere Orga pere@orga.cat"

ARG profiler

COPY . /srv/app

WORKDIR /srv/app

# Apache settings
COPY .docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
RUN rm -f /etc/apache2/mods-enabled/deflate.conf && \
    sed -i 's/ServerTokens OS/ServerTokens Prod/g' /etc/apache2/conf-available/security.conf

# PHP settings, including enabling assertions (DEV only).
RUN sed 's/expose_php = On/expose_php = Off/g' /usr/local/etc/php/php.ini-production > /usr/local/etc/php/php.ini && \
    sed -i 's/zend.assertions = -1/zend.assertions = 1/g' /usr/local/etc/php/php.ini && \
    sed -i 's/;assert.exception = On/assert.exception = On/g' /usr/local/etc/php/php.ini

# PHP extensions
RUN apt-get update -y && \
    apt-get install --no-install-recommends -y libicu-dev zlib1g-dev && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    docker-php-ext-install pdo_mysql opcache intl && \
    pecl install apcu-5.1.22 && \
    docker-php-ext-enable apcu
COPY .docker/php/apcu.ini /usr/local/etc/php/conf.d/apcu.ini

# apcu.php uses gd, but we usually don't care
#RUN apt-get update -y && apt-get install --no-install-recommends -y libpng-dev && apt-get clean && rm -rf /var/lib/apt/lists/* && docker-php-ext-install gd

# SPX profiler
# hadolint ignore=DL3003
RUN if [ "$profiler" = "spx" ]; then \
        apt-get update -y && \
        apt-get install --no-install-recommends -y zlib1g-dev && \
        apt-get clean && \
        rm -rf /var/lib/apt/lists/* && \
        cp -a vendor/noisebynorthwest/php-spx . && \
        cd php-spx && \
        phpize && \
        ./configure && \
        make && \
        make install && \
        echo 'extension=spx.so' >> /usr/local/etc/php/php.ini && \
        cp ../.docker/php/spx.ini /usr/local/etc/php/conf.d/spx.ini; \
    fi

# Tideways XHProf fork, plus the GUI coming from https://github.com/longxinH/xhprof
# hadolint ignore=DL3003
RUN if [ "$profiler" = "xhprof" ]; then \
        cp -a vendor/tideways/php-xhprof-extension . && \
        cd php-xhprof-extension && \
        phpize && \
        ./configure && \
        make && \
        make install && \
        echo 'auto_prepend_file=/srv/app/src/tideways_xhprof_prepend.php' >> /usr/local/etc/php/php.ini && \
        echo 'auto_append_file=/srv/app/src/tideways_xhprof_append.php' >> /usr/local/etc/php/php.ini && \
        echo 'extension=tideways_xhprof.so' >> /usr/local/etc/php/php.ini && \
        cd .. && \
        apt-get update -y && \
        apt-get install --no-install-recommends -y graphviz && \
        apt-get clean && \
        rm -rf /var/lib/apt/lists/* && \
        cp -a vendor/longxinh/xhprof /usr/local/lib/php/xhprof && \
        grep -h -F -v '</VirtualHost>' .docker/apache/vhost.conf > /etc/apache2/sites-available/000-default.conf && \
        cat .docker/apache/tideways_xhprof.conf >> /etc/apache2/sites-available/000-default.conf && \
        echo '</VirtualHost>' >> /etc/apache2/sites-available/000-default.conf; \
    fi

# XHProf fork (not working on ARM64)
RUN if [ "$profiler" = "xhprof-fork" ]; then \
        apt-get update -y && \
        apt-get install --no-install-recommends -y git graphviz && \
        apt-get clean && \
        rm -rf /var/lib/apt/lists/* && \
        pecl install xhprof-2.3.9 && \
        docker-php-ext-enable xhprof && \
        echo 'auto_prepend_file=/srv/app/src/xhprof.php' >> /usr/local/etc/php/php.ini && \
        grep -h -F -v '</VirtualHost>' .docker/apache/vhost.conf > /etc/apache2/sites-available/000-default.conf && \
        cat .docker/apache/xhprof.conf >> /etc/apache2/sites-available/000-default.conf && \
        echo '</VirtualHost>' >> /etc/apache2/sites-available/000-default.conf; \
    fi

# Apache mods
RUN a2enmod rewrite && a2enmod headers && a2enmod brotli

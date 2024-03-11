FROM php:8.3.3-apache-bookworm
LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="Apache-based image with Apache and PHP."

ARG profiler

# Set working directory
WORKDIR /srv/app

# Install install-php-extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Remove some Apache default settings provided by Debian
# Do not expose Apache version in header
# Disable unnecessary Apache modules
# Enable Apache modules
# Use PHP default production settings, but do not expose PHP version
# Install PHP extensions, ommitting OPCache to reduce Docker build times
RUN rm -f /etc/apache2/mods-enabled/deflate.conf /etc/apache2/mods-enabled/alias.conf && \
    sed -i 's/ServerTokens OS/ServerTokens Prod/g' /etc/apache2/conf-available/security.conf && \
    a2dismod -f access_compat auth_basic authn_core authn_file authz_host authz_user autoindex negotiation reqtimeout setenvif status && \
    a2enmod rewrite headers brotli && \
    sed 's/expose_php = On/expose_php = Off/g' /usr/local/etc/php/php.ini-production > /usr/local/etc/php/php.ini && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions apcu intl pdo_mysql

# Copy configuration files
COPY .docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
#COPY .docker/php/production.ini /etc/php83/conf.d/production.ini

# apcu.php uses gd, but we usually don't care
#RUN install-php-extensions gd

# Copy project files
COPY docroot ./docroot
COPY scripts ./scripts
COPY src ./src
COPY tmp ./tmp

# SPX profiler
RUN if [ "$profiler" = "spx" ]; then \
        install-php-extensions spx && \
        { \
        echo "[spx]"; \
        echo "spx.http_enabled = 1"; \
        echo "spx.http_ip_whitelist = \"*\""; \
        echo "spx.http_key = \"dev\""; \
        } > /usr/local/etc/php/conf.d/spx.ini; \
    fi

# XHProf profiler
RUN if [ "$profiler" = "xhprof" ]; then \
        install-php-extensions xhprof && \
        sed -i '/<\/VirtualHost>/d' /etc/apache2/sites-available/000-default.conf && \
        { \
        echo "Alias /admin/xhprof /usr/local/lib/php/xhprof_html"; \
        echo "<Directory /usr/local/lib/php/xhprof_html/>"; \
        echo "    Options Indexes FollowSymLinks"; \
        echo "    AllowOverride FileInfo"; \
        echo "    Require all granted"; \
        echo "    php_value auto_prepend_file none"; \
        echo "</Directory>"; \
        } >> /etc/apache2/sites-available/000-default.conf; \
        echo '</VirtualHost>' >> /etc/apache2/sites-available/000-default.conf && \
        { \
        echo "[xhprof]"; \
        echo "auto_prepend_file = /srv/app/src/xhprof.php"; \
        echo "xhprof.collect_additional_info = 1"; \
        echo "xhprof.output_dir = /tmp"; \
        } > /usr/local/etc/php/conf.d/xhprof.ini; \
    fi

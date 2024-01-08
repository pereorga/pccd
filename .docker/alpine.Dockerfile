FROM alpine:3.19
LABEL maintainer="pere@orga.cat"
LABEL description="Alpine based image with Apache and PHP."

# hadolint ignore=DL3018
RUN apk --no-cache --update add \
        apache2 \
        apache2-brotli \
        php83-apache2 \
        php83-apcu \
        php83-common \
        php83-intl \
        php83-mbstring \
        php83-opcache \
        php83-pdo_mysql \
        php83-session \
    && mkdir -p /srv/app/docroot

# Set working directory
WORKDIR /srv/app

# Copy project files
COPY . .

# Copy Apache configuration files
COPY .docker/apache/vhost.conf /etc/apache2/conf.d/vhost.conf

# Start Apache
ENTRYPOINT [".docker/docker-entrypoint-alpine.sh"]

FROM php:8.5-apache

WORKDIR /var/www/vufind

# Install system dependencies, libraries, netcat, and JRE (for SolrMarc indexing)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libxml2-dev \
    libxslt1-dev \
    libldap2-dev \
    default-jre-headless \
    default-mysql-client \
    netcat-openbsd \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Configure & install PHP extensions required by VuFind 11
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        intl \
        gd \
        zip \
        soap \
        ldap \
        xsl

# Enable Apache modules
RUN a2enmod rewrite headers

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Application tree (see .dockerignore for what is excluded)
COPY . /var/www/vufind/

# SolrJ for SolrMarc indexing, taken from the Solr image

# PHP dependencies. --no-scripts skips VuFind's phing hook, which tries to download
# Solr and Swagger into the tree; Solr comes from the Solr image instead.
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist --no-progress --no-scripts --working-dir=/var/www/vufind

# Normalize line endings on shell scripts (checkouts on Windows use CRLF)
RUN for d in . harvest import util; do sed -i 's/\r$//' /var/www/vufind/$d/*.sh 2>/dev/null || true; done \
    && chmod +x /var/www/vufind/*.sh 2>/dev/null || true

# Static asset and cache symlinks, created at build time rather than at startup
RUN ln -s /var/www/vufind/themes /var/www/vufind/public/themes \
    && ln -s /var/www/vufind/local/cache/public /var/www/vufind/public/cache \
    && mkdir -p /var/www/vufind/local/cache/public /var/www/vufind/local/config/vufind \
    && chown -R www-data:www-data /var/www/vufind/local

# Apache virtual host: serve public/ at /, keep the internal /vufind rewrites
COPY config/vufind/httpd-vufind.conf /etc/apache2/conf-available/vufind.conf
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/vufind/public|' /etc/apache2/sites-available/000-default.conf \
    && sed -i \
        -e 's|/usr/local/vufind|/var/www/vufind|g' \
        -e 's|^AliasMatch ^/vufind/|AliasMatch ^/|' \
        -e 's|^Alias /vufind /var/www/vufind/public|# Alias removed: DocumentRoot serves public/|' \
        -e 's|<Location /vufind>|<Location />|' \
        /etc/apache2/conf-available/vufind.conf \
    && a2enconf vufind

# Environment variables
ENV VUFIND_HOME=/var/www/vufind
ENV VUFIND_LOCAL_DIR=/var/www/vufind/local
ENV VUFIND_ENV=production

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]

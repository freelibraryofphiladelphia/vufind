FROM alpine:3.18

# Install required packages
RUN apk add --no-cache \
    nginx \
    php81-fpm \
    php81-phar \
    php81-curl \
    php81-dom \
    php81-gd \
    php81-intl \
    php81-json \
    php81-ldap \
    php81-mbstring \
    php81-mysqli \
    php81-pdo_mysql \
    php81-soap \
    php81-xml \
    php81-tokenizer \
    php81-fileinfo \
    php81-simplexml \
    php81-xmlwriter \
    php81-openssl \
    php81-iconv \
    php81-zip \
    php81-session \
    php81-fpm \
    mysql-client \
    git \
    composer \
    openjdk11

# Configure PHP-FPM
RUN sed -i 's/user = nobody/user = nginx/g' /etc/php81/php-fpm.d/www.conf && \
    sed -i 's/group = nobody/group = nginx/g' /etc/php81/php-fpm.d/www.conf

# Create directories
RUN mkdir -p /var/www/vufind /var/www/vufind/local/cache/cli /var/www/vufind/local/config/vufind

WORKDIR /var/www/vufind

COPY . /var/www/vufind/
COPY nginx.conf /etc/nginx/http.d/default.conf

RUN composer install \
    && php install.php --non-interactive --overridedir=/var/www/vufind/local

RUN chown -R nginx:nginx /var/www/vufind/local/cache /var/www/vufind/local/config/vufind \
    && chmod 777 /var/www/vufind/local/cache/cli

ENV VUFIND_HOME=/var/www/vufind
ENV VUFIND_LOCAL_DIR=/var/www/vufind/local

EXPOSE 80

# Start Nginx and PHP-FPM
CMD php-fpm81 && nginx -g 'daemon off;'

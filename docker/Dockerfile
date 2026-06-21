FROM php:8.4-fpm-alpine

# Install dependencies (including Node.js for asset building)
RUN apk update && apk add --no-cache \
    nginx \
    supervisor \
    bash \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    sqlite-dev \
    zip \
    unzip \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite bcmath gd zip

# Configure PHP upload limits
RUN echo "upload_max_filesize = 40M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 45M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Configure PHP-FPM to use a socket
RUN sed -i 's|^listen = .*|listen = /run/php/php-fpm.sock|' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's|^;listen.owner = .*|listen.owner = www-data|' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's|^;listen.group = .*|listen.group = www-data|' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's|^;listen.mode = .*|listen.mode = 0666|' /usr/local/etc/php-fpm.d/www.conf && \
    echo "listen = /run/php/php-fpm.sock" > /usr/local/etc/php-fpm.d/zz-docker.conf

# Create required directories
RUN mkdir -p /run/nginx /run/php /var/log/supervisor

# Copy Nginx and Supervisor configs
COPY docker/nginx/default-prod.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application
WORKDIR /var/www/html
COPY . .

# Install composer dependencies for production
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Build frontend assets
RUN npm install && \
    npm run build && \
    rm -rf node_modules

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache \
    && chmod 666 database/database.sqlite 2>/dev/null || true

EXPOSE 80

RUN which supervisord && ls -l /usr/bin/supervisord || (echo "supervisord not found" && exit 1)

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]

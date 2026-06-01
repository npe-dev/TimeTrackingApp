#!/bin/sh
set -e

# Install composer dependencies if vendor/ is missing
if [ ! -d /var/www/html/vendor ]; then
    echo "Installing composer dependencies..."
    composer install --working-dir=/var/www/html
fi

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create SQLite database if it doesn't exist
if [ ! -f /var/www/html/database/database.sqlite ]; then
    touch /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
fi

# Run migrations if AUTO_MIGRATE is set
if [ "${AUTO_MIGRATE}" = "true" ]; then
    php /var/www/html/artisan migrate --force
fi

exec "$@"

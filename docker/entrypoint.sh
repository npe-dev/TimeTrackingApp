#!/bin/sh
set -e

echo "Setting up permissions..."

# Ensure storage and bootstrap/cache are writable
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Ensure database directory and file are writable
chown -R www-data:www-data /var/www/html/database
chmod -R 775 /var/www/html/database

# Create SQLite database if it doesn't exist
if [ -f /var/www/html/database/database.sqlite ]; then
    chmod 666 /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
    echo "Database file permissions set"
else
    touch /var/www/html/database/database.sqlite
    chmod 666 /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
    echo "Created database file with correct permissions"
fi

# Install composer dependencies if vendor/ is missing (dev mode)
if [ ! -d /var/www/html/vendor ]; then
    echo "Installing composer dependencies..."
    composer install --working-dir=/var/www/html
fi

# Run migrations
if [ "${AUTO_MIGRATE}" = "true" ] || [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "Running migrations..."
    php /var/www/html/artisan migrate --force
fi

echo "Starting services..."
exec "$@"

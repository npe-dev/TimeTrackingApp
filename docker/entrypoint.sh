#!/bin/sh
set -e

echo "Setting up permissions..."

# Ensure the framework storage subdirs exist. The prod compose mounts ./storage
# from the host, which shadows the image's storage dir; git does not track empty
# dirs, so framework/{views,cache,sessions} can be missing on a fresh volume.
# Without them Blade compilation (incl. the /up healthcheck view) fails, the
# container never reports healthy, and the deploy aborts.
mkdir -p \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs

# Create the SQLite database file if missing (cheap — a single file). Every
# container needs it to exist, so this runs unconditionally.
if [ ! -f /var/www/html/database/database.sqlite ]; then
    touch /var/www/html/database/database.sqlite
    echo "Created database file"
fi

# Recursively fixing ownership/permissions on the shared ./storage + ./database
# bind-mounts is expensive. The app, scheduler and queue containers all boot from
# this image and mount the SAME volumes, so running `chown -R` in each just pins
# the CPU with concurrent recursive chowns over one volume. Only the primary
# container does it (FIX_PERMISSIONS defaults to true so standalone use still
# works); the scheduler/queue set FIX_PERMISSIONS=false and share the result.
if [ "${FIX_PERMISSIONS:-true}" = "true" ]; then
    echo "Fixing permissions on storage, bootstrap/cache and database..."
    # The whole database/ dir must be www-data-owned: SQLite in WAL mode writes
    # not just the .sqlite file but the -wal/-shm sidecars beside it, so the
    # directory itself has to be writable by the app. (The deploy workflow chowns
    # the checkout back to the deploy user before `git reset`, so re-owning these
    # git-tracked dirs here does not block the next pull.)
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
    chmod 664 /var/www/html/database/database.sqlite
else
    echo "FIX_PERMISSIONS=false — skipping recursive chown (shared volume handled by the primary container)"
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

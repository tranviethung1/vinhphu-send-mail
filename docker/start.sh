#!/bin/sh

set -e

echo "Starting application..."

# PostgreSQL SSL configuration
# Neon PostgreSQL requires SSL but does NOT require client certificates
# Unset any client cert paths to prevent libpq from trying to read /root/.postgresql/postgresql.crt
# which causes "Permission denied" errors when running as www-data
unset PGSSLCERT
unset PGSSLKEY
unset PGSSLROOTCERT
unset PGSSLCRL

# Require SSL mode (Neon enforces SSL connection)
export PGSSLMODE=${PGSSLMODE:-require}
export DB_SSLMODE=${DB_SSLMODE:-require}

# Change HOME so libpq no longer looks in /root/.postgresql for client certs
export HOME=/var/www

# Run Laravel optimizations
echo "Running Laravel optimizations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations (only on first deploy or when needed)
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force --no-interaction
fi

# Start supervisor
echo "Starting supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

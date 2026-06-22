#!/bin/sh
# Wrapper script for Laravel queue worker to ensure proper SSL environment

# Unset client cert paths to prevent libpq from trying to read /root/.postgresql/postgresql.crt
unset PGSSLCERT
unset PGSSLKEY
unset PGSSLROOTCERT
unset PGSSLCRL

# Require SSL mode (Neon enforces SSL connection)
export PGSSLMODE=${PGSSLMODE:-require}
export DB_SSLMODE=${DB_SSLMODE:-require}

# Execute the queue worker command
exec php /var/www/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=300 "$@"


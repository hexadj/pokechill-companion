#!/bin/sh
set -e

cd /var/www/html

mkdir -p var/cache var/log
chown -R www-data:www-data var

if [ -n "${DATABASE_URL:-}" ]; then
  echo "Waiting for database..."
  attempts=0
  max_attempts=60
  until su www-data -s /bin/sh -c "cd /var/www/html && php bin/console dbal:run-sql 'SELECT 1'" > /dev/null 2>&1; do
    attempts=$((attempts + 1))
    if [ "$attempts" -ge "$max_attempts" ]; then
      echo "Database not reachable after ${max_attempts} attempts."
      exit 1
    fi
    sleep 2
  done

  su www-data -s /bin/sh -c "cd /var/www/html && php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration"
  su www-data -s /bin/sh -c "cd /var/www/html && php bin/console cache:warmup --env=${APP_ENV:-prod} --no-debug"
fi

exec "$@"

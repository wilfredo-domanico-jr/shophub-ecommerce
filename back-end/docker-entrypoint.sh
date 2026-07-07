#!/bin/sh
set -e

if [ ! -f .env ]; then
  cp .env.example .env
fi

php artisan key:generate --force --no-interaction

echo "Waiting for the database to be ready..."
attempt=0
max_attempts=30
until php artisan migrate --force --no-interaction; do
  attempt=$((attempt + 1))
  if [ "$attempt" -ge "$max_attempts" ]; then
    echo "Database did not become ready in time, giving up."
    exit 1
  fi
  sleep 3
done

php artisan db:seed --force --no-interaction

php artisan storage:link --force || true

exec "$@"

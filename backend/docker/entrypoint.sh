#!/bin/sh
set -e

cd /var/www/html

# ---------------------------------------------------------------------------
# 1. Wait for MySQL (containers may start before the database is ready)
# ---------------------------------------------------------------------------
echo "==> Waiting for MySQL at ${DB_HOST:-mysql}:${DB_PORT:-3306} ..."

php <<'PHP'
<?php
$host = getenv('DB_HOST') ?: 'mysql';
$port = (int) (getenv('DB_PORT') ?: 3306);
$db   = getenv('DB_DATABASE') ?: 'ecommerce';
$user = getenv('DB_USERNAME') ?: 'laravel';
$pass = getenv('DB_PASSWORD') ?: '';

for ($attempt = 1; $attempt <= 60; $attempt++) {
    try {
        new PDO(
            "mysql:host={$host};port={$port};dbname={$db}",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3],
        );

        exit(0); // database accepted the connection
    } catch (Throwable $e) {
        echo "    attempt {$attempt}/60 failed, retrying in 2s...\n";
        sleep(2);
    }
}

fwrite(STDERR, "MySQL not reachable after 120 seconds. Aborting.\n");
exit(1);
PHP

# ---------------------------------------------------------------------------
# 2. Storage skeleton + permissions (needed when the volume is brand new)
# ---------------------------------------------------------------------------
mkdir -p storage/app/public \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/framework/testing \
         storage/logs \
         bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# ---------------------------------------------------------------------------
# 3. Public storage symlink (files uploaded via the API are served from here)
# ---------------------------------------------------------------------------
if [ ! -e public/storage ]; then
    echo "==> Creating public/storage symlink ..."
    su-exec www-data php artisan storage:link
fi

# ---------------------------------------------------------------------------
# 4. Generate APP_KEY on first boot (skip when one is provided via env)
# ---------------------------------------------------------------------------
if [ -z "${APP_KEY:-}" ] && ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    echo "==> Generating APP_KEY ..."
    su-exec www-data php artisan key:generate --force
fi

# ---------------------------------------------------------------------------
# 5. Migrations - only in the web/serve container, not in queue workers
# ---------------------------------------------------------------------------
case "$*" in
    *"artisan serve"*)
        echo "==> Running database migrations ..."
        su-exec www-data php artisan migrate --force
        ;;
esac

echo "==> Starting: $*"
exec su-exec www-data "$@"

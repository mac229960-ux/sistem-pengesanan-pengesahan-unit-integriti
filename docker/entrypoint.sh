#!/bin/sh
set -e
cd /var/www/html

# Tunggu MySQL sedia
until php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Exception $e) { exit(1); }'; do
  echo "Menunggu pangkalan data..."; sleep 3
done

if [ "$1" = "apache2-foreground" ]; then
  php artisan migrate --force
  # Data rujukan & pengguna awal hanya jika jadual pengguna kosong
  if [ "$(php artisan tinker --execute='echo \App\Models\User::count();' 2>/dev/null | tail -1)" = "0" ]; then
    php artisan db:seed --force
  fi
  php artisan storage:link >/dev/null 2>&1 || true
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache

exec "$@"

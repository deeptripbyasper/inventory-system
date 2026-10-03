#!/bin/bash
set -e

# Support dynamic PORT environment variable (for Render, Railway, Fly.io, Heroku, Cloud Run)
HTTP_PORT="${PORT:-80}"
echo "[INIT] Configuring Apache to listen on port ${HTTP_PORT}..."
sed -i "s/Listen [0-9]*/Listen ${HTTP_PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${HTTP_PORT}>/" /etc/apache2/sites-available/000-default.conf

# Ensure database and config directory write permissions
mkdir -p /var/www/html/database /var/www/html/config /var/www/html/assets
chown -R www-data:www-data /var/www/html/database /var/www/html/config /var/www/html/assets
chmod -R 777 /var/www/html/database /var/www/html/config

echo "[INIT] Starting Apache web server on port ${HTTP_PORT}..."
exec apache2-foreground


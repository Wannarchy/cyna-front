#!/bin/sh
set -e

PORT="${PORT:-10000}"

if [ ! -f /var/www/html/.env ]; then
  {
    [ -n "$CYNA_API_URL" ] && printf 'CYNA_API_URL=%s\n' "$CYNA_API_URL"
    [ -n "$CYNA_API_SSL_VERIFY" ] && printf 'CYNA_API_SSL_VERIFY=%s\n' "$CYNA_API_SSL_VERIFY"
    [ -n "$STRIPE_PUBLISHABLE_KEY" ] && printf 'STRIPE_PUBLISHABLE_KEY=%s\n' "$STRIPE_PUBLISHABLE_KEY"
    [ -n "$STRIPE_SECRET_KEY" ] && printf 'STRIPE_SECRET_KEY=%s\n' "$STRIPE_SECRET_KEY"
    [ -n "$STRIPE_WEBHOOK_SECRET" ] && printf 'STRIPE_WEBHOOK_SECRET=%s\n' "$STRIPE_WEBHOOK_SECRET"
  } > /var/www/html/.env
  chown www-data:www-data /var/www/html/.env
  chmod 640 /var/www/html/.env
fi

sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf 2>/dev/null || true
sed -i "s/:80 /:${PORT} /" /etc/apache2/sites-enabled/000-default.conf 2>/dev/null || true

exec apache2-foreground

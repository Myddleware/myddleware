#!/bin/bash

# Myddleware cron execution script
# This script is called by the cron daemon every minute

# Don't exit on error - we want cron to log everything
set +e

# Ensure we're in the correct directory
if [ ! -f "bin/console" ]; then
  cd /var/www/html || exit 1
fi

# Ensure log directory exists and is writable
if [ ! -d "/var/log" ]; then
  mkdir -p /var/log
fi
chmod 777 /var/log 2>/dev/null || true

# Log function
log() {
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

log "Starting Myddleware cron job"

# Verify directory permissions
if [ ! -d "var/cache" ] || [ ! -d "var/log" ]; then
  log "Creating cache and log directories..."
  mkdir -p var/cache var/log
  chmod -R 700 var/cache
  chmod -R 700 var/log
  chown -R www-data:www-data var/cache var/log
fi

# Run the cron command
log "Executing: php bin/console myddleware:cronrun --env=background"
php bin/console myddleware:cronrun --env=background

if [ $? -eq 0 ]; then
  log "Cron job completed successfully"
else
  log "Cron job finished with exit code: $?"
fi

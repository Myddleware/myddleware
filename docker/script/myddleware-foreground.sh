#!/usr/bin/env bash

set -e

LOG_FILE="/var/log/myddleware-startup.log"
mkdir -p /var/log
{
  echo "===== Myddleware Docker Startup $(date '+%Y-%m-%d %H:%M:%S') ====="

  mkdir -p var/cache var/log
  chmod -R 700 var/cache
  chown -R www-data:www-data var/cache
  chmod -R 700 var/log
  chown -R www-data:www-data var/log
  echo "[OK] Directory permissions set"

  ## Extend Hosts
  echo "[START] Updating hosts file..."
  if [ -f hosts ]; then
    cat hosts >> /etc/hosts 2>&1
    echo "[OK] Hosts file updated"
  else
    echo "[WARN] No hosts file found"
  fi

  ## Start logging services
  echo "[START] Starting rsyslog service..."
  if service rsyslog start >> /tmp/rsyslog.log 2>&1; then
    echo "[OK] Rsyslog started"
  else
    echo "[ERROR] Rsyslog failed to start"
    cat /tmp/rsyslog.log
  fi

  ## Start cron daemon
  echo "[START] Starting cron daemon..."
  if service cron start >> /tmp/cron-start.log 2>&1; then
    echo "[OK] Cron daemon started"
  else
    echo "[ERROR] Cron daemon failed to start"
    cat /tmp/cron-start.log
  fi

  ## Verify cron configuration
  echo "[VERIFY] Cron configuration..."
  echo "Cron daemon status:"
  service cron status || echo "[WARN] Cron status check failed"

  echo "Checking /etc/cron.d/ directory:"
  ls -la /etc/cron.d/

  echo "Cron file permissions:"
  ls -la /etc/cron.d/myddleware || echo "[WARN] Myddleware cron file not found"

  echo "Cron daemon process:"
  pgrep -l cron || echo "[WARN] No cron process found"

  echo "Cron logs:"
  tail -20 /var/log/cron.log 2>/dev/null || echo "[INFO] Cron log not yet created"

  sleep 2
  echo "[OK] Cron daemon initialized"
  echo "===== Starting Apache ====="

} | tee -a "$LOG_FILE"

## Start Apache
echo "====[ START APACHE ]===="
apache2-foreground "$@"

#!/bin/bash

# Health check script for Myddleware Docker container
# This script verifies that the cron daemon is running and properly configured

set -e

HEALTH_LOG="/var/log/myddleware-health-check.log"

check_item() {
  local name="$1"
  local status="$2"
  local details="$3"

  if [ "$status" = "OK" ]; then
    echo "[OK] $name" | tee -a "$HEALTH_LOG"
    if [ -n "$details" ]; then
      echo "      $details" | tee -a "$HEALTH_LOG"
    fi
  else
    echo "[ERROR] $name" | tee -a "$HEALTH_LOG"
    if [ -n "$details" ]; then
      echo "      $details" | tee -a "$HEALTH_LOG"
    fi
  fi
}

{
  echo "===== Myddleware Health Check $(date '+%Y-%m-%d %H:%M:%S') ====="

  # Check cron daemon process
  if pgrep -x cron > /dev/null; then
    check_item "Cron daemon" "OK" "Cron process is running"
  else
    check_item "Cron daemon" "ERROR" "Cron process not found"
  fi

  # Check cron file exists
  if [ -f /etc/cron.d/myddleware ]; then
    check_item "Cron file" "OK" "Cron configuration file exists"
    echo "Contents:" | tee -a "$HEALTH_LOG"
    cat /etc/cron.d/myddleware | sed 's/^/  /' | tee -a "$HEALTH_LOG"
  else
    check_item "Cron file" "ERROR" "Cron configuration file not found"
  fi

  # Check cron file permissions
  if [ -f /etc/cron.d/myddleware ]; then
    PERMS=$(stat -c %a /etc/cron.d/myddleware 2>/dev/null || echo "unknown")
    if [ "$PERMS" = "644" ]; then
      check_item "Cron file permissions" "OK" "Permissions: $PERMS"
    else
      check_item "Cron file permissions" "ERROR" "Permissions: $PERMS (should be 644)"
    fi
  fi

  # Check myddleware-cron.sh script
  if [ -x /usr/local/bin/myddleware-cron.sh ]; then
    check_item "Myddleware cron script" "OK" "Script is executable"
  else
    check_item "Myddleware cron script" "ERROR" "Script not found or not executable"
  fi

  # Check rsyslog (not needed in Docker - cron logs directly to file)
  check_item "Rsyslog service" "OK" "Not needed in Docker (cron logs directly to /var/log/myddleware-cron.log)"

  # Check myddleware cron log file
  if [ -f /var/log/myddleware-cron.log ]; then
    LINES=$(wc -l < /var/log/myddleware-cron.log)
    check_item "Myddleware cron log" "OK" "Found with $LINES lines"
    echo "Recent cron log entries:" | tee -a "$HEALTH_LOG"
    tail -10 /var/log/myddleware-cron.log | sed 's/^/  /' | tee -a "$HEALTH_LOG"
  else
    check_item "Myddleware cron log" "WARN" "Cron log file not found (cron may not have executed yet)"
  fi

  # Check Apache/PHP
  if pgrep -f apache2 > /dev/null; then
    check_item "Apache service" "OK" "Apache is running"
  else
    check_item "Apache service" "ERROR" "Apache not running"
  fi

  # Check PHP executable
  if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v 2>&1 | head -1)
    check_item "PHP" "OK" "$PHP_VERSION"
  else
    check_item "PHP" "ERROR" "PHP not found in PATH"
  fi

  # Check application directories
  if [ -f /var/www/html/bin/console ]; then
    check_item "Myddleware app" "OK" "Application found at /var/www/html"
  else
    check_item "Myddleware app" "ERROR" "Application not found"
  fi

  # Check application cache/log directories
  if [ -d /var/www/html/var/cache ] && [ -d /var/www/html/var/log ]; then
    check_item "Application directories" "OK" "Cache and log directories exist"
  else
    check_item "Application directories" "ERROR" "Cache or log directories missing"
  fi

  echo "===== Health Check Complete ====="

} | tee -a "$HEALTH_LOG"

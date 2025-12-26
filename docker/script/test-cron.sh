#!/bin/bash

# Test script to manually run the myddleware cron job with full debugging
# This helps verify if the script itself works or if cron daemon isn't picking it up

set -e

echo "===== Testing Myddleware Cron Job ====="
echo "Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

echo "[TEST] Checking if myddleware-cron.sh is executable..."
if [ -x /usr/local/bin/myddleware-cron.sh ]; then
  echo "[OK] Script is executable"
else
  echo "[ERROR] Script is not executable"
  exit 1
fi

echo ""
echo "[TEST] Running myddleware-cron.sh as www-data user..."
su - www-data -s /bin/bash -c '/usr/local/bin/myddleware-cron.sh' 2>&1 | tee /tmp/manual-cron-test.log

echo ""
echo "[TEST] Checking if log file was created..."
if [ -f /var/log/myddleware-cron.log ]; then
  echo "[OK] Log file created: /var/log/myddleware-cron.log"
  echo "[INFO] Log contents:"
  cat /var/log/myddleware-cron.log
else
  echo "[ERROR] Log file not created"
fi

echo ""
echo "[TEST] Checking cron daemon logs..."
if [ -f /var/log/cron.log ]; then
  echo "[OK] Cron log found:"
  tail -20 /var/log/cron.log
else
  echo "[INFO] Cron log not found (cron daemon may not have system logging)"
fi

echo ""
echo "===== Test Complete ====="

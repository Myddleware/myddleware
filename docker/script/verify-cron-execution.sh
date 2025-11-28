#!/bin/bash

# This script verifies that cron is actually executing commands
# by installing a test cron job and checking if it runs

echo "===== Cron Execution Verification ====="
echo "Test started at: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# Create a test cron file
TEST_CRON="/etc/cron.d/test-cron-execution"
TEST_LOG="/var/log/test-cron-execution.log"

echo "[STEP 1] Creating test cron job..."
cat > "$TEST_CRON" << 'EOF'
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
SHELL=/bin/bash
# Test every minute
* * * * * root echo "Cron test executed at $(date '+\%Y-\%m-\%d \%H:\%M:\%S')" >> /var/log/test-cron-execution.log 2>&1
EOF

chmod 644 "$TEST_CRON"
echo "[OK] Test cron file created at $TEST_CRON"

echo ""
echo "[STEP 2] Test cron file contents:"
cat "$TEST_CRON"

echo ""
echo "[STEP 3] Checking cron daemon status..."
if pgrep -x cron > /dev/null; then
  echo "[OK] Cron daemon is running"
else
  echo "[ERROR] Cron daemon is not running"
  exit 1
fi

echo ""
echo "[STEP 4] Reloading cron daemon..."
service cron reload || true

echo ""
echo "[STEP 5] Waiting 65 seconds for cron to execute..."
sleep 65

echo ""
echo "[STEP 6] Checking if test job executed..."
if [ -f "$TEST_LOG" ]; then
  echo "[OK] Test cron job executed! Log file created:"
  cat "$TEST_LOG"
else
  echo "[ERROR] Test cron job did NOT execute!"
  echo ""
  echo "Debugging info:"
  echo "- Cron daemon process: $(pgrep -l cron)"
  echo "- Test cron file: $(ls -la $TEST_CRON)"
  echo "- /etc/cron.d directory: $(ls -la /etc/cron.d/)"
fi

echo ""
echo "[STEP 7] Cleaning up test cron file..."
rm -f "$TEST_CRON" "$TEST_LOG"

echo ""
echo "===== Verification Complete ====="

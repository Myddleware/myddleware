#!/bin/bash

LOG_FILE="var/log/prod.log"
TEMP_FILE="${LOG_FILE}.tmp"

# Check if the log file exists
if [ ! -f "$LOG_FILE" ]; then
  echo "❌ Log file not found: $LOG_FILE"
  exit 1
fi

# Filter only lines containing "app.CRITICAL"
grep "app\.CRITICAL" "$LOG_FILE" > "$TEMP_FILE"

# Replace the original log with the filtered one
mv "$TEMP_FILE" "$LOG_FILE"

echo "✅ Cleaned log file — only 'app.CRITICAL' entries remain."

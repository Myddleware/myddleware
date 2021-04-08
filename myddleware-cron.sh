#!/bin/bash
set -e

source /run/crond.env

echo $(date): Start Myddleware Sync >> /var/log/cron.log
echo $(date): Start Myddleware Sync >> /var/log/myddleware.log

php /var/www/html/bin/console myddleware:jobScheduler --env=background >> /var/log/myddleware.log

echo $(date): End Myddleware Sync >> /var/log/cron.log
echo $(date): End Myddleware Sync >> /var/log/myddleware.log

## Custom Scheduler
if [[ -f /var/www/html/scheduler.sh ]]; then
  chmod +x /var/www/html/scheduler.sh
  bash /var/www/html/scheduler.sh >> /var/www/html/var/logs/scheduler.log 2>&1
  chmod 777 /var/www/html/var/logs/scheduler.log
fi

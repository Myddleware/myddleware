#!/bin/bash
set -e

echo $(date): Start Myddleware Sync >> /var/log/cron.log
echo $(date): Start Myddleware Sync > /var/log/myddleware.log

php /var/www/html/bin/console myddleware:jobScheduler --env=background >> /var/log/myddleware.log

echo $(date): End Myddleware Sync >> /var/log/cron.log
echo $(date): End Myddleware Sync >> /var/log/myddleware.log

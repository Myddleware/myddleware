#!/usr/bin/env bash

#@cd var/logs; if [ -f background.log ]; then cp background.log rotate/background.$(shell date +%s).log; truncate -s 0 background.log; fi
#@cd var/logs; if [ -f dev.log ]; then cp dev.log rotate/dev.$(shell date +%s).log; truncate -s 0 dev.log; fi
#@cd var/logs; if [ -f monitoring.log ]; then cp monitoring.log rotate/monitoring.$(shell date +%s).log; truncate -s 0 monitoring.log; fi
#@cd var/logs; if [ -f php.log ]; then cp php.log rotate/php.$(shell date +%s).log; truncate -s 0 php.log; fi
#@cd var/logs; if [ -f prod.log ]; then cp prod.log rotate/prod.$(shell date +%s).log; truncate -s 0 prod.log; fi
#@cd var/logs; if [ -f scheduler.log ]; then cp scheduler.log rotate/scheduler.$(shell date +%s).log; truncate -s 0 scheduler.log; fi
#@cd var/logs; if [ -f vtigercrm.log ]; then cp vtigercrm.log rotate/vtigercrm.$(shell date +%s).log; truncate -s 0 vtigercrm.log; fi
#@cd var/logs; if [ -f last_query.log ]; then cp last_query.log rotate/last_query.$(shell date +%s).log; truncate -s 0 last_query.log; fi
#@cd var/logs; if [ -f vtigercrm.json ]; then cp vtigercrm.json rotate/vtigercrm.$(shell date +%s).json; truncate -s 0 vtigercrm.json; fi

chmod 777 -R var/logs || true

Welcome to Myddleware community and thanks for joining us !

Myddleware 2.1 is the customisable free open-source platform that facilitates data migration and synchonisation between applications.

<img class="alignnone size-large wp-image-447" src="http://community.myddleware.com/wp-content/uploads/2016/11/create_rule_view-1024x596.png" alt="create_rule_view" width="640" height="373" />

<a href="http://community.myddleware.com/" target="_blank">On our community website,</a> you’ll find everything you’re looking for to master Myddleware, from step-by-step tutorials, to English and French forums. You can also tailor Myddleware to your needs by creating you custom code. Please use <a href="https://github.com/Myddleware" target="_blank">our github</a> to share it.

This community is ours : let’s all contribute, make it a friendly, helpful space where we can all find what we’re looking for!

Please don’t hide any precious skills from us, whether it is coding, translation, connectors creation, .... the list goes on! The whole community could then benefit from these!

Applications connected : SAP CRM, SuiteCRM, Prestashop, Bittle, Dolist, Salesforce, SugarCRM, Mailchimp, Magento, Sage CRM, Moodle, Evetbrite, ERPNext.  We also connect File via ftp and Database.

Find us here : <a href="http://www.myddleware.com">www.myddleware.com</a>

<em>We created it, you own it!</em>

<img class="alignnone size-medium wp-image-161" src="http://community.myddleware.com/wp-content/uploads/2016/09/myddleware_logo-300x215.jpg" alt="myddleware_logo" width="300" height="215" />



# Sviluppo

## Requisiti sul PC
- Docker 18 o superiore 
- Docker Compose 1.17 o superiore

## Avviare i containers
```bash
docker-compose up -d
```

## Verifica stato containers
```bash
docker-compose ps
```

## Dati di accesso Vtiger
Vtiger1: admin/admin
Vtiger2: admin/admin

## Installare le dipendence
```bash
docker-compose run --rm myddleware php composer.phar install --ignore-platform-reqs --no-scripts
```

## Preparazione dei file e cartelle
```bash
docker-compose run --rm myddleware php composer.phar run-script post-install-cmd
```

## Installare il database
```bash
docker-compose run --rm myddleware ./prepare-database.sh
```

## Dati di accesso Myddleware
Myddleware: admin/admin

## Permessi di scrittura
```bash
linux: sudo chmod 777 -R var/cache var/logs
macos: find var/cache -type d -exec sudo chmod 0777 {} +
       find var/logs -type d -exec sudo chmod 0777 {} +
```

## Aggiornare le dipendenze
```bash
sudo rm -fr var/cache/*
sudo rm -rf vendor && composer update -v --ignore-platform-reqs --no-scripts
docker-compose run --rm myddleware php composer.phar run-script post-install-cmd
```

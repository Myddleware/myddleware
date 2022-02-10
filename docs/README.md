# Myddleware

> Welcome to the Myddleware community and thank you for joining us!

![Myddleware Logo](http://community.myddleware.com/wp-content/uploads/2016/09/myddleware_logo-300x215.jpg)

Myddleware is the customisable free open-source platform that facilitates data migration and synchronisation between applications.

![Create Rule View](http://community.myddleware.com/wp-content/uploads/2016/11/create_rule_view-1024x596.png)

[On our community website,](http://community.myddleware.com) you’ll find everything you’re looking for to master Myddleware, from step-by-step tutorials, to English and French forums. You can also tailor Myddleware to your needs by creating you custom code. Please use [our github](https://github.com/Myddleware) to share it.

This community is ours : let’s all contribute, make it a friendly, helpful space where we can all find what we’re looking for!

Please don’t hide any precious skills from us, whether it is coding, translation, connectors creation, .... the list goes on! The whole community could then benefit from these!

Applications currently connected :

- Bittle
- Dolist
- ERPNext
- Eventbrite
- Facebook
- Hubspot
- Magento
- Mailchimp
- Mautic
- Microsoft SQL
- Moodle
- MySQL
- OracleDB
- Prestashop
- Sage CRM
- Salesforce
- SAP CRM
- SugarCRM
- SuiteCRM
- Vtiger
- Woocommerce
- Wordpress
- Zuora  

We also connect File via FTP and Database.

Find us here : [www.myddleware.com](https://www.myddleware.com)

*We created it, you own it!*

## Technical Requirements

!> We are actively working on a [Docker](https://www.docker.com/) configuration for Myddleware. Once released, you will be able to install Myddleware using Docker too, which means we will ensure all requirements below are met.

To use Myddleware you need the following on your web server :

- A web server such as [Apache](https://httpd.apache.org/)
- [MySQL](https://www.mysql.com/downloads/) version 5.7 or above or [MariaDB](https://mariadb.org/download/?t=mariadb&p=mariadb&r=10.6.5&os=windows&cpu=x86_64&pkg=msi&m=xtom_ams)
- [PHP](https://www.php.net/downloads.php) version 7.4 or above. The following PHP extensions need to be installed & enabled (they usually are by default):
  - Ctype
  - Iconv
  - JSON
  - PCRE
  - Session
  - SimpleXML
  - Tokenizer
- [composer](https://getcomposer.org/download/)
- the [Symfony CLI](https://symfony.com/download)
- [Node.js](https://nodejs.org/de/download/) version 14.16.1
- [yarn](https://yarnpkg.com/getting-started/install) version [1.22.10](https://classic.yarnpkg.com/lang/en/docs/install/#windows-stable )

Myddleware uses [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/2.11/tutorials/getting-started.html#getting-started-with-doctrine) so you will need to have the PDO driver installed for the database server you intend to use.

It is possible that depending on your webserver configuration there might be some missing requirements. We strongly recommend running the following command in a terminal to ensure all requirements are met :

``` symfony check:requirements ```


## Install Myddleware

### Download Myddleware

You can download Myddleware in 2 different ways :

- clone the [Github repository](https://github.com/Myddleware/myddleware)
- download a zip file [here](http://www.myddleware.com/solution/download)

#### Downloading from Github

At the root of your webserver (for example /var/wwww/html), open a terminal and type the following command:

```bash

    git clone git@github.com:Myddleware/myddleware.git 
 
 ```

or

``` git clone https://github.com/Myddleware/myddleware.git ```

Then, navigate to the newly created Myddleware folder with :

```bash

cd myddleware 

```

##### Install PHP dependencies with Composer

``` composer install ```

##### Install Javascript libraries

``` yarn install ```

##### Build assets

``` yarn build ```

#### Installing from the archive

Once you've downloaded our ready-to-use [Myddleware archive](http://www.myddleware.com/solution/download), you need to unzip it at the root of your webserver directory (for example /var/www/html). You can unzip it manually or using the following command : 

```bash

    unzip myddleware.zip -d <myddleware_dirname>

```

## Basic usage

### Connect your applications to Myddleware

### Create a rule

## Administration tasks

### Add users

### Promote a user

### Demote a user

## Supported Connectors

## Contributing

> Myddleware relies on the [Symfony Framework](https://symfony.com/), a free open-source PHP framework. If you would like to contribute to our source code, you can first familiarise yourself with the [Symfony documentation](https://symfony.com/doc/current/index.html)

### Create your own connectors


## Going further

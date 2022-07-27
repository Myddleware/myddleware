# Myddleware

> Welcome to the Myddleware community and thanks for joining us!

![Myddleware Logo](http://community.myddleware.com/wp-content/uploads/2016/09/myddleware_logo-300x215.jpg)

Myddleware is the customisable free open-source platform that facilitates data migration and synchronisation between applications

![Create Rule View](http://community.myddleware.com/wp-content/uploads/2016/11/create_rule_view-1024x596.png)

[On our documentation website,](https://myddleware.github.io/myddleware) you’ll find everything you’re looking for to master Myddleware, including step-by-step tutorials. You can also tailor Myddleware to your needs by creating you custom code. Please use [our github](https://github.com/Myddleware) to share it.

This community is ours : let’s all contribute, make it a friendly, helpful space where we can all find what we’re looking for!

Please don’t hide any precious skills from us, whether it is coding, translation, connectors creation, .... the list goes on! The whole community could then benefit from these!

Applications connected : SAP CRM, SuiteCRM, Prestashop, Bittle, Dolist, Salesforce, SuiteCRM, SugarCRM, Mailchimp, Sage CRM, Moodle, Eventbrite, ERPNext, Facebook, Hubspot, Magento, Mautic, Microsoft SQL, MySQL, OracleDB, Vtiger, Wordpress, Woocommerce.  We also connect File via ftp and Database.

Find us here : [www.myddleware.com](https://www.myddleware.com)

*We created it, you own it!*

%[{ requirements.md }]%

## Install Myddleware

### Download Myddleware

You can download Myddleware in 2 different ways :

<!-- tabs:start -->

#### **Git clone**

If you are familiar with Git, Github & command lines, you can clone our [Github repository](https://github.com/Myddleware/myddleware)

##### Setting up the project

At the root of your webserver (for example /var/wwww/html), open a terminal and type the following command:

```git
git clone git@github.com:Myddleware/myddleware.git 
 ```

or

```git
git clone https://github.com/Myddleware/myddleware.git
```

Then, navigate to the newly created Myddleware folder with :

```bash
cd myddleware 
```

##### Install PHP dependencies with Composer

```bash
composer install 
```

##### Install Javascript libraries

```bash
yarn install 
```

##### Build assets

```bash
yarn build 
```

#### **Donwload zip archive**

Download the Myddleware zip file [here](http://www.myddleware.com/solution/download)

##### Installing from the archive

Once you've downloaded our ready-to-use [Myddleware archive](http://www.myddleware.com/solution/download), you need to unzip it at the root of your webserver directory (for example /var/www/html). You can unzip it manually or using the following command :

```bash
unzip myddleware.zip -d <myddleware_dirname>
```

<!-- tabs:end -->

### Setting up your Myddleware environment

At this stage, to set up Myddleware, you can either follow our installation wizard by going to your Myddleware URL or if you're comfortable with using a terminal, you can execute a series of commands.

<!-- tabs:start -->
#### **Setup from the web browser**

You need to go to the URL where Myddleware will be located, for instance : ```http://<yourdomain>.com/<myddlewarefolder>/myddleware/public/```
From there, you need to click on "Install Myddleware" and follow the directions of the Installation Wizard.

##### Check requirements

Here, Myddleware will check whether your server meets all the requirements for Myddleware to be able to run (are there any missing PHP extensions ? are there any permissions issues ?)

##### Connect to your Myddleware database

Before proceeding to this step, please ensure you've already created the database you intend to use for Myddleware. Then you can fill in the form. Once you've saved & clicked next, Myddleware will attempt to connect to the database using the information you've provided. If there are any errors, a message will appear to let you know what went wrong. If everything is OK, you can continue to the next step.

##### Create your Myddleware admin user

Fill in the form to create your Myddleware credentials (email, username & password). Once this is done, you should be redirected to the Myddleware homepage.

#### **Setup from a terminal**

##### Create your environment file

At the root of your /myddleware directory, you need to create a .env.local file (it should be at the same level as the .env & .env.example files).

<!-- tabs:end -->

## Basic usage

### Connect your applications to Myddleware

*This section is still under construction*

### Create a rule

*This section is still under construction*

## Administration tasks

*This section is still under construction*

### Add users

### Promote a user

### Demote a user

## Supported Connectors

*This section is still under construction*

## Contributing

> Myddleware relies on the [Symfony Framework](https://symfony.com/), a free open-source PHP framework. If you would like to contribute to our source code, you can first familiarise yourself with the [Symfony documentation](https://symfony.com/doc/current/index.html)

%[{ dev_guide.md }]%

## Going further

*This section is still under construction*

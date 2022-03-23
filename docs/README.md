# Myddleware

> Welcome to the Myddleware community and thank you for joining us!

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/b22fda90ea2b4a9a9542b8026734e840)](https://www.codacy.com/gh/Myddleware/myddleware/dashboard?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=Myddleware/myddleware&amp;utm_campaign=Badge_Grade)
[![GitHub release (latest by date)](https://img.shields.io/github/v/release/Myddleware/myddleware)](https://github.com/Myddleware/myddleware)
![GitHub repo size](https://img.shields.io/github/repo-size/Myddleware/myddleware)
![GitHub top language](https://img.shields.io/github/languages/top/Myddleware/myddleware)
![GitHub package.json dependency version (prod)](https://img.shields.io/github/package-json/dependency-version/Myddleware/myddleware/bootstrap)
[![GitHub issues](https://img.shields.io/github/issues/Myddleware/myddleware)](https://github.com/Myddleware/myddleware/issues)
[![GitHub closed issues](https://img.shields.io/github/issues-closed/Myddleware/myddleware)](https://github.com/Myddleware/myddleware/issues?q=is%3Aissue+is%3Aclosed)
[![GitHub](https://img.shields.io/github/license/Myddleware/myddleware)](https://github.com/Myddleware/myddleware/blob/main/LICENSE)
![GitHub last commit](https://img.shields.io/github/last-commit/Myddleware/myddleware)
[![GitHub contributors](https://img.shields.io/github/contributors/Myddleware/myddleware)](https://github.com/Myddleware/myddleware/graphs/contributors)
[![Website](https://img.shields.io/website?url=https%3A%2F%2Fwww.myddleware.com%2F)](https://www.myddleware.com/)
[![docsify](https://img.shields.io/badge/documented%20with-docsify-cc00ff.svg)](https://docsify.js.org/)
![GitHub followers](https://img.shields.io/github/followers/Myddleware?style=social)
[![YouTube Channel Views](https://img.shields.io/youtube/channel/views/UCxI0ziSiRXXTqQ-XfFJr7-w?style=social)](https://www.youtube.com/channel/UCxI0ziSiRXXTqQ-XfFJr7-w)

Myddleware is the customisable free open-source platform that facilitates data migration and synchronisation between applications.

![Create Rule View](http://community.myddleware.com/wp-content/uploads/2016/11/create_rule_view-1024x596.png)

[On our documentation website,](https://myddleware.github.io/myddleware) you’ll find everything you’re looking for to master Myddleware, including step-by-step tutorials. You can also tailor Myddleware to your needs by creating you custom code. Please use [our github](https://github.com/Myddleware) to share it.

This community is ours : let’s all contribute, make it a friendly, helpful space where we can all find what we’re looking for!

Please don’t hide any precious skills from us, whether it is coding, translation, connectors creation, .... the list goes on! The whole community could then benefit from these!

%[{ connectors.md }]%

Find us here : [www.myddleware.com](https://www.myddleware.com)

*We created it, you own it!*

%[{ requirements.md }]%

## Install Myddleware

### Download Myddleware

You can download Myddleware in 2 different ways :

<!-- tabs:start -->

#### **Git clone**

If you are familiar with Git, Github & command lines, you can clone our [Github repository](https://github.com/Myddleware/myddleware)

#### Setting up the project

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

#### Install PHP dependencies with Composer

```bash
composer install 
```

#### Install Javascript libraries

```bash
yarn install 
```

#### Build assets

```bash
yarn build 
```

#### **Donwload zip archive**

Download the Myddleware zip file [here](http://www.myddleware.com/solution/download)

#### Installing from the archive

Once you've downloaded our ready-to-use [Myddleware archive](http://www.myddleware.com/solution/download), you need to unzip it at the root of your webserver directory (for example /var/www/html). You can unzip it manually or using the following command :

```bash
unzip myddleware.zip -d <myddleware_dirname>
```

#### **Docker install**

You can use the Docker config files provided to install Myddleware if you want to reproduce our developer's environment or deploy it using Kubernetes for example.

#### Using the Myddleware Makefile

> Make isn't available on Windows systems. If you want to use them on your Windows machine, you will need to set up [WSL](https://docs.microsoft.com/en-us/windows/wsl/).

Various useful commands are available on our [Makefile](https://github.com/Myddleware/myddleware/blob/main/Makefile). For instance, you can use the following to build & run the Docker container for Myddleware :

```bash
## List all your Docker containers
make ps

## Run Myddleware with Docker Compose
make run-with-compose

## Run Myddleware with Docker
make build
make run
```

#### Build the Myddleware image with docker-compose (developer's mode)

##### Build the container locally

Run the following commands in your myddleware directory :

```docker-compose
docker-compose up --build

```

This will build a Myddleware image containing the Myddleware container (PHP with Apache) as well as a Node.js container to handle assets and a MySQL container for your database.

Once the images are up and running, you need to go to the Node.js terminal and type :

```yarn
yarn install
yarn build

```

Once your assets are built, you can now go to <http://localhost:30080>, where you should see the Myddleware homescreen.

To connect to the MySQL database, use the following credentials :

- **username** : myddleware
- **database** : myddleware
- **password** : secret

#### Build the Myddleware image with Docker(developer's mode)

##### Build the container

If you choose to build Myddleware using Docker on its own, you will need to set up your database environment variables and connect Myddleware to it as this image doesn't provide a Myddleware database.

```docker
docker build . -t myddleware
```

#### Run

```docker
docker run -d -p 30080:80 myddleware
```

You can then access your Myddleware instance by going to ```http://localhost:30080/index.php```

<!-- tabs:end -->

## Setting up your Myddleware environment

At this stage, to set up Myddleware, you can either follow our installation wizard by going to your Myddleware URL or if you're comfortable with using a terminal, you can execute a series of commands.

<!-- tabs:start -->
### **Setup from the web browser**

You need to go to the URL where Myddleware will be located, for instance : ```http://<yourdomain>.com/<myddlewarefolder>/myddleware/public/```
From there, you need to click on "Install Myddleware" and follow the directions of the Installation Wizard.

#### Check requirements

Here, Myddleware will check whether your server meets all the requirements for Myddleware to be able to run (are there any missing PHP extensions ? are there any permissions issues ?)

#### Connect to your Myddleware database

Before proceeding to this step, please ensure you've already created the database you intend to use for Myddleware. Then you can fill in the form. Once you've saved & clicked next, Myddleware will attempt to connect to the database using the information you've provided. If there are any errors, a message will appear to let you know what went wrong. If everything is OK, you can continue to the next step.

#### Create your Myddleware admin user

Fill in the form to create your Myddleware credentials (email, username & password). Once this is done, you should be redirected to the Myddleware homepage.

### **Setup from a terminal**

#### Create your environment file

At the root of your /myddleware directory, you need to create a .env.local file (it should be at the same level as the .env & .env.example files). If you've followed the installation from GitHub above, all you will need to do here is to fill in the .env.local file with the following information :

```env
DATABASE_URL="mysql://username:password@host:port/dbname"
APP_ENV=prod
APP_DEBUG=false
APP_SECRET=ThisSecretIsNotSoSecretChangeIt
MAILER_URL=gmail://smtp.example.com:465?encryption=ssl&auth_mode=login&username=&password=
```

The DATABASE_URL variable will contain the values used by Myddleware to connect to your actual database, so you must replace each placeholder value with your credentials.

The MAILER_URL is optional. It is used by Myddleware to send you notification emails on some occasions such as when a task failed or some documents are in error. You need to configure it to match your SMTP server's credentials.

<!-- tabs:end -->

%[{ basic_usage.md }]%
%[{ admin_tasks.md }]%

## Supported Connectors

%[{ connectors.md }]%

## Contributing

> Myddleware relies on the [Symfony Framework](https://symfony.com/), a free open-source PHP framework. If you would like to contribute to our source code, you can first familiarise yourself with the [Symfony documentation](https://symfony.com/doc/current/index.html)

%[{ dev_guide.md }]%

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

#### **Setup from a terminal **


##### Create your environment file

At the root of your /myddleware directory, you need to create a .env.local file (it should be at the same level as the .env & .env.example files).

<!-- tabs:end -->

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

*This section is still under construction*

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
<a href="https://www.opensourceagenda.com/projects/myddleware"><img src="https://www.opensourceagenda.com/projects/myddleware/reviews/badge.svg" alt="Open Source Agenda"></a>

Myddleware is the customisable free open-source platform that facilitates data migration and synchronisation between applications.

![Create Myddleware "Customer" Rule View with WooCommerce Customers & Vtiger Opportunities modules](docs/images/rule_create_woo_vtiger.png)

[On our documentation website,](https://myddleware.github.io/myddleware-docs) you’ll find everything you’re looking for to master Myddleware, including step-by-step tutorials. You can also tailor Myddleware to your needs by creating you custom code. Please use [our github](https://github.com/Myddleware) to share it.

This community is ours : let’s all contribute, make it a friendly, helpful space where we can all find what we’re looking for!

Please don’t hide any precious skills from us, whether it is coding, translation, connectors creation, .... the list goes on! The whole community could then benefit from these!


Find us here : [www.myddleware.com](https://www.myddleware.com)

*We created it, you own it!*

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

Once your assets are built, you can now go to http://localhost:30080, where you should see the Myddleware homescreen.

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
## Contributing

> Myddleware relies  on the [Symfony Framework](https://symfony.com/), a free open-source PHP framework. If you would like to contribute to our source code, you can first familiarise yourself with the [Symfony documentation](https://symfony.com/doc/current/index.html)

Myddleware is an Open Source community project. All contributions are welcome, no matter how big or small. There are many ways to contribute to the project.
You can of course help us improve and debug our source code, but you can also start by simply reporting issues & bugs or suggesting new features on our [GitHub Issues forum](https://github.com/Myddleware/myddleware/issues), or review and leave comments on [Pull Requests](https://github.com/Myddleware/myddleware/pulls).

If you would like to contribute to Myddleware source code, please refer to the [developer's guide](dev_guide.md) section of this documentation. We also kindly ask
that you follow the [Symfony Coding Standards](https://symfony.com/doc/current/contributing/code/standards.html) to ensure uniformity and clarity of our source code across Myddleware.
We recommend using the [PHP CS Fixer](https://github.com/FriendsOfPhp/PHP-CS-Fixer) tool to ensure these standards are respected across your contributed code.

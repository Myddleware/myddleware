# Technical Requirements

!> We are actively working on a [Docker](https://www.docker.com/) configuration for Myddleware. Once released, you will be able to install Myddleware using Docker too, which means we will ensure all requirements below are met.

To use Myddleware you need the following on your web server :

- A web server such as [Apache](https://httpd.apache.org/)
- [MySQL](https://www.mysql.com/downloads/) version 5.7 or above or [MariaDB](https://mariadb.org/download/?t=mariadb&p=mariadb&r=10.6.5&os=windows&cpu=x86_64&pkg=msi&m=xtom_ams)

| Myddleware versions  | [PHP version](https://www.php.net/downloads.php) | [Node.js version](https://nodejs.org/de/download/) |
|----------------------|--------------------------------------------------|----------------------------------------------------|
| 3.2.0 and below      | 7.4 or 8.0                                       | 14, 15 and 16                                      |
| 3.3.0                | 8.1 or 8.2.0                                     | 16 or above                                        |

- The following PHP extensions need to be installed & enabled (they usually are by default):
  - Ctype
  - Iconv
  - JSON
  - PCRE
  - Session
  - SimpleXML
  - Tokenizer
- [composer](https://getcomposer.org/download/)
- the [Symfony CLI](https://symfony.com/download)
- [yarn](https://yarnpkg.com/getting-started/install) version [1.22.17](https://classic.yarnpkg.com/lang/en/docs/install/#windows-stable ) or above

Myddleware uses [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/2.11/tutorials/getting-started.html#getting-started-with-doctrine) so you will need to have the PDO driver installed for the database server you intend to use.

It is possible that depending on your webserver configuration there might be some missing requirements. We strongly recommend running the following command in a terminal to ensure all requirements are met :

``` symfony check:requirements ```

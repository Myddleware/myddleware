# Administration tasks

> Myddleware comes with a set of extra admin features that are not activated by default. If you would like to enable these features for your Myddleware user, you will need to promote your user to the Super Admin role.

## Add users

In your Myddleware root directory, type the following command in a terminal:

```bash
php bin/console myddleware:add-user
```

To add a user with the role Super Admin by default, simply type :

```bash
php bin/console myddleware:add-user --superadmin
```

![Add Myddleware User command prompt](images/add_user_command.png)

## Promote an existing user

Some advanced Myddleware features such as cancelling or deleting all documents from a rule are restricted to Super Admin users. To enable these privileges for a Myddleware user, once in the myddleware directory, type the following command :

```bash
php bin/console myddleware:promote-user <email> ROLE_SUPER_ADMIN
```

Or simply type this command, a command prompt will assist you:

```bash
php bin/console myddleware:promote-user
```

Type the user’s email address, press Enter then type ROLE_SUPER_ADMIN and press Enter again.

![Promote Myddleware User command prompt](images/promote_user_command.png)

## Demote a user

If you wish to remove some special roles from a user privileges (such as ROLE_SUPER_ADMIN), use the following command :

```bash
php bin/console myddleware:demote-user
```

![Demote Myddleware User command prompt](images/demote_user_command.png)

## Upgrading Myddleware

*This section is still under construction*

### Init & fetch from GitHub

We suggest you use git to update Myddleware. If you don’t have git on your server, [here are the instructions on how to install it](https://git-scm.com/download/linux).
Before doing anything else, create a backup of your Myddleware instance before updating.
After the backup, go to the root directory of Myddleware and run the command as stated below to update all the Myddleware files.
If you've never used git with Myddleware, please run these commands from your Myddleware root directory:

```git
git init
git remote add -t main origin https://github.com/Myddleware/myddleware.git
git fetch
git checkout origin/main -ft
```

### Upgrading

You can upgrade Myddleware with this command, which will run a series of jobs in the background :

```
php bin/console myddleware:upgrade --env=background
```

### Upgrading (alternative)

If you encountered an issue during the upgrade you can do it step by step by following this tutorial instead.

#### Fetch from GitHub

```git
git pull
```

**TODO: this section is still under construction**

If you get an error message below after trying to pull, you might have changed at least one file in the Myddleware standard code. 
Please refer to ``Ensuring your custom code is upgrade-safe in Myddleware``  in the **Developer's guide** section of this doc. It will help you manage conflicts & transferring your custom code safely. 
You can also delete these files, run ```git pull``` again and you will get the latest version of these files. However, if you do, you will probably lose your custom code & files.


#### Upgrade PHP dependencies

```
composer install
```

#### Synchronise Myddleware Database


````
php bin/console doctrine:schema:update --force --env=background
````

#### Synchronise Myddleware config inside the database

````
php bin/console doctrine:fixtures:load --append --env=background
````

#### Clear cache files

````
php bin/console cache:clear
````

> Alternatively, if you encountered issues with this command, you can try to run ````rm -rf var/cache/*```` instead

#### Upgrade JavaScript libraries

!> If you do not have [yarn](https://yarnpkg.com/getting-started/install#nodejs-1610-1) package manager installed on your server, please do so as it is now required in Myddleware 3+.

Run the following command to update your JavaScript libraries.

````
yarn install
````

#### Build for prod

Once that's done, you now need to build your Myddleware instance for production using the following command : 

```
yarn build 
```


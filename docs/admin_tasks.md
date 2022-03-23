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

## Demote a user

If you wish to remove some special roles from a user privileges (such as ROLE_SUPER_ADMIN), use the following command :

```bash
php bin/console myddleware:demote-user
```

## Email notifications configuration

Myddleware is able to send notifications and alerts using scheduled tasks. For this, you need to configure your SMTP parameters in the 'SMTP config' tab of your user menu :

*This section is still under construction*

## Setting up a cron task

*This section is still under construction*

## Handling transfers in error

*This section is still under construction*

## Setting up a cron task

*This section is still under construction*

## Upgrading Myddleware

*This section is still under construction*
# Dora Boateng API

# Local setup

## Software

- Git
- PHP, MCrypt, MB String, ext-dom (php-xml), CUrl
- Composer

## Settign up OAuth

`php artisan passport:install`

## Seeding the database

    php artisan backup:sync

# Deploying

## Staging

`git push heroku`

## Production (with Envoy)

`envoy run deploy`

Sample `config` file:
```
Host dora-boateng
    User boateng
    HostName IP_ADDRESS
    IdentityFile ~/.ssh/id_rsa
```

# Maintenance

## Creating database backups

Logical dumps can be created using one of the following syntax:
```sql
mysqldump boateng > boateng.sql
mysqldump --databases db_name1 [db_name2 ...] > my_databases.sql
mysqldump --all-databases > all_databases.sql
```

# Miscelaneous

## OAuth clients

New clients can be created from the command line (see `php artisan help passport:client`).

## Obfuscator

To play with the obfuscator:
```bash
php artisan tinker
>>> $obfuscator = app('Obfuscator');
>>> $obfuscator->encode(1);
```

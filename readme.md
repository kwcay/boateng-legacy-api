# Dora Boateng API

# Local setup

## Software

- Git
- PHP, MCrypt, MB String, ext-dom (php-xml), CUrl
- Composer

## Settign up OAuth

`php artisan passport:install`

## Seeding the database

- Todo

# Deploying

## Staging

`git push heroku`

## Production (with Envoy)

`envoy run deploy`

Sample `config` file:
```
Host production-server
    HostName 45.55.60.14
    User boateng-user
    IdentityFile ~/.ssh/id_production
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

# Dora Boateng API

# Deploying

## Staging
`git push heroku`

## Production

Make sure Vagrant can access the production server.

Sample `config` file:
```
Host production-server
    HostName 45.55.60.14
    User boateng-user
    IdentityFile ~/.ssh/id_production
```

Run `envoy run deploy` from Vagrant.

# Maintenance

## Creating database backups

Logical dumps can be created using one of the following syntax:
```sql
mysqldump boateng > boateng.sql
mysqldump --databases db_name1 [db_name2 ...] > my_databases.sql
mysqldump --all-databases > all_databases.sql
```

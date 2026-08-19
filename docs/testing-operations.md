# Testing and Operations

## Local verification

```bash
php artisan test
vendor/bin/pint --test
npm run build
php artisan route:list
```

Feature tests use SQLite in memory and database refresh. Test role/permission behavior through public routes or services; do not rely solely on package internals.

## Deployment

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan admin:permissions-sync
php artisan admin:menu-regenerate
php artisan optimize
```

Configure a real mail transport before relying on forgot-password. Change the bootstrap password immediately and do not expose an installation with the known credential; password changes are not forced by middleware. The scheduler must run every minute so the daily activity and abandoned learning-material image cleanup tasks execute.

Permission synchronization is exact and may delete obsolete database permissions. Review configuration changes before production deployment and deploy permission/menu changes together.

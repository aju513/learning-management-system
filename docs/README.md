# Admin Foundation Documentation

This repository is a Laravel 12, Blade, Alpine.js, Tailwind CSS 4, and TailAdmin admin foundation.

## Start here

1. Install dependencies with `composer install` and `npm install`.
2. Configure `.env`, including the database and mail transport.
3. Run `php artisan migrate --seed`.
4. Sign in at `/admin/login` with `admin@admin.com` / `admin`.
The bootstrap credential is intentionally predictable and must never remain unchanged. The permission command creates it only when the users table is empty. Password changes remain available from the admin password screen, but are not forced by middleware.

## Documentation map

- [Architecture](architecture.md): layers, data flow, routes, and boundaries.
- [Authentication and authorization](authentication-authorization.md): Fortify, users, roles, safeguards, and auditing.
- [Permissions and menus](menus-permissions.md): configuration schemas and regeneration commands.
- [UI components](ui-components.md): layouts and TailAdmin component conventions.
- [Feature development](feature-development.md): required implementation workflow.
- [Testing and operations](testing-operations.md): verification, deployment, and maintenance commands.
- [Learning Management System](lms.md): roles, course delivery, assessments, progress, reporting, demo accounts, and future boundaries.
- [Future LMS roadmap](future-lms-roadmap.md): deferred test scheduling, history, ownership, collaboration, and scoped administration.

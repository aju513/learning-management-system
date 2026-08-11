# Admin Foundation Engineering Rules

Read `docs/README.md` before changing application behavior. These rules apply to every new feature and refactor in this repository.

## Required feature flow

Every non-trivial feature must use:

`Route -> FormRequest -> Controller -> Service -> Repository contract -> Eloquent repository -> Model`

- Controllers authorize, call a service, and return a response. Do not place queries, transactions, or business rules in controllers.
- Form requests own input validation and request-level authorization.
- Services own workflows, transactions, state changes, and activity logging.
- Repositories own persistence, filtering, pagination, and reusable queries. Bind every contract in `AppServiceProvider`.
- Use policies, `can` middleware, `Gate::authorize`, or FormRequest authorization on the server. Hiding a Blade link is never sufficient authorization.
- Fortify-owned login and password-reset endpoints are the only package-managed exception.

## Required feature checklist

1. Add migrations, model/factory changes, and repository methods.
2. Add FormRequests, a thin controller, and a service workflow.
3. Add stable permissions to `config/permissions.php` and run `php artisan admin:permissions-sync`.
4. Add named routes and, when needed, an entry in `config/admin-menu.php`; run `php artisan admin:menu-regenerate`.
5. Build Blade views from the TailAdmin patterns documented in `docs/ui-components.md`.
6. Add Pest feature tests for success, validation, authorization, and important failure paths.
7. Update the relevant file in `docs/` in the same change.

## Project invariants

- Use the default `web` guard and permission checks (`can`) rather than hard-coded role checks. The only role-name exception is the `super-admin` Gate override and its safeguards.
- Permissions are code-owned. Do not create application permissions outside `config/permissions.php`.
- The sidebar is generated from `config/admin-menu.php`; do not hard-code navigation in Blade.
- Do not add public registration, email verification, passkeys, 2FA, direct user permissions, or soft deletes without an explicit architecture decision and documentation update.
- Never log passwords, tokens, credentials, session data, or other secrets.
- Use named routes, strict validation, database transactions for multi-write workflows, and TailAdmin design tokens rather than introducing unrelated styling.
- Before handoff, run `php artisan test`, `vendor/bin/pint --test`, and `npm run build`.

# Authentication and Authorization

## Fortify scope

Fortify is mounted under `/admin` and enables login, logout, remember-me, forgot-password, and reset-password. Public registration, email verification, 2FA, passkeys, and Fortify's optional profile/password endpoints are disabled.

Login is limited to active users and throttled to five attempts per minute per normalized email/IP pair. Authenticated inactive users are logged out by middleware. Passwords must have at least 12 characters with upper/lowercase letters, a number, and a symbol.

Local environments also expose four guest-accessible demo-login buttons for the seeded super administrator, administrator, instructor, and trainee accounts. Each button submits a CSRF-protected, IP-throttled POST request, resolves the active account by its configured email, authenticates it on the `web` guard, and regenerates the session. The shortcuts are controlled by `LMS_DEMO_LOGIN_ENABLED`, default to enabled only when `APP_ENV=local`, and must remain disabled in exposed environments. Normal Fortify login remains available regardless of this setting.

## Bootstrap account

`admin:permissions-sync` creates `admin@admin.com` with password `admin` only when there are no users and assigns `super-admin`. The bootstrap password is documented for local setup only; change it before exposing the application, but the application does not force a password-change redirect.

If users already exist, the command assigns `super-admin` to user ID 1. If ID 1 does not exist, it reports a warning and does not promote another user.

## Fixed roles and portal resolution

Every user has exactly one of four code-owned roles: `super-admin`, `admin`, `instructor`, or `trainee`. `/portal` rejects roleless, multi-role, and unsupported-role accounts rather than guessing a destination. The resolved portal dashboards are `/super-admin`, `/admin`, `/instructor`, and `/learning`, respectively. Portal-entry permissions are mutually exclusive, including for Super Admin; the Super Admin override continues to grant non-portal oversight abilities.

There is no custom-role CRUD or role picker. Super Admin creates fixed Admin, Instructor, and Trainee accounts from separate screens. Admin creates fixed Instructor and Trainee accounts. User forms contain name, email, password/confirmation, and active/inactive status. Users are hard-deleted; there are no soft deletes. The last super administrator cannot be deleted, demoted, or deactivated.

Permissions are never assigned directly to users. The four roles receive exact, code-owned permission matrices from `config/lms.php`. `admin:permissions-sync` refuses to mutate data when users have multiple roles, no role outside the bootstrap exception, or an assigned unsupported role. This prevents role combinations from accumulating menus or crossing portal boundaries.

## Activity log

Spatie Activitylog records login/logout/failure, demo login, password reset/change, profile changes, user administration, course applications and reviews, permission synchronization, and menu regeneration. Passwords, tokens, and session values are excluded. `/super-admin/activity-log` is read-only and permission-protected. Retention defaults to 365 days through `ACTIVITY_LOG_RETENTION_DAYS`.

# Authentication and Authorization

## Fortify scope

Fortify is mounted under `/admin` and enables login, logout, remember-me, forgot-password, and reset-password. Public registration, email verification, 2FA, passkeys, and Fortify's optional profile/password endpoints are disabled.

Login is limited to active users and throttled to five attempts per minute per normalized email/IP pair. Authenticated inactive users are logged out by middleware. Passwords must have at least 12 characters with upper/lowercase letters, a number, and a symbol.

## Bootstrap account

`admin:permissions-sync` creates `admin@admin.com` with password `admin` only when there are no users and assigns `super-admin`. The bootstrap password is documented for local setup only; change it before exposing the application, but the application does not force a password-change redirect.

If users already exist, the command assigns `super-admin` to user ID 1. If ID 1 does not exist, it reports a warning and does not promote another user.

## User lifecycle

User forms contain name, email, password/confirmation, active/inactive status, and roles. Users are hard-deleted; there are no soft deletes. The last super administrator cannot be deleted, demoted, or deactivated. Authorized self-deletion is permitted when another super administrator exists and invalidates the current session.

Permissions are never assigned directly to users. Users receive roles, and roles receive permissions from the code-owned catalog. `super-admin` cannot be renamed or deleted, and roles with assigned users cannot be deleted.

## Activity log

Spatie Activitylog records login/logout/failure, password reset/change, profile changes, user/role administration, permission synchronization, and menu regeneration. Passwords, tokens, and session values are excluded. `/admin/activity-log` is read-only and permission-protected. Retention defaults to 365 days through `ACTIVITY_LOG_RETENTION_DAYS`.

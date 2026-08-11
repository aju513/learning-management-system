# Architecture

## Stack and boundary

The admin application is a server-rendered Laravel monolith under `/admin`. Blade renders TailAdmin views, Alpine.js provides local UI state, and Tailwind CSS 4 provides design tokens. The root URL currently redirects to the admin boundary and remains available for a future public application.

## Request flow

Non-trivial features follow `Route -> FormRequest -> Controller -> Service -> Repository -> Model`.

- Routes use stable `admin.*` names and authentication/authorization middleware.
- FormRequests validate and authorize input.
- Controllers contain transport concerns only.
- Services coordinate workflows, database transactions, state changes, and activity records.
- Repository contracts isolate persistence and query behavior; Eloquent implementations are container-bound in `AppServiceProvider`.
- Models define relationships, casts, and small state helpers.

Fortify owns login, logout, and password-reset controllers. The application provides its Blade views and active-user authentication callback. Profile and password changes use the project layers instead of Fortify's optional update endpoints.

## Authorization

The sidebar and Blade `@can` directives improve usability, but route middleware, FormRequest authorization, and Gate checks enforce security. The `super-admin` role receives a `Gate::before` allow result. Other application code checks permissions rather than role names.

## Data ownership

- `config/permissions.php` owns permission names.
- `config/admin-menu.php` owns navigation metadata.
- Services own role synchronization and audit event properties.
- Repositories own filtering and pagination.

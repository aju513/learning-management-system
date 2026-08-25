# Architecture

## Stack and boundary

The LMS is a server-rendered Laravel monolith with four role-specific portal boundaries: `/super-admin`, `/admin`, `/instructor`, and `/learning`. Blade renders TailAdmin views, Alpine.js provides local UI state, and Tailwind CSS 4 provides design tokens. Authentication remains shared under Fortify's `/admin` endpoints; `/` and `/portal` resolve an authenticated user to the dashboard for their one fixed system role.

System-owned interface labels support English and Nepali (`ne`) through Laravel translations. The language selector stores the current label locale in the session and applies it across shared layouts, navigation, forms, dashboards, reports, and authentication screens. User-entered content such as names, course titles, descriptions, questions, and answers is never translated.

## Request flow

Non-trivial features follow `Route -> FormRequest -> Controller -> Service -> Repository -> Model`.

- Routes use stable `super-admin.*`, `admin.*`, `instructor.*`, and `learning.*` names with authentication, active-user, and portal authorization middleware.
- FormRequests validate and authorize input.
- Controllers contain transport concerns only.
- Services coordinate workflows, database transactions, state changes, and activity records.
- Repository contracts isolate persistence and query behavior; Eloquent implementations are container-bound in `AppServiceProvider`.
- Models define relationships, casts, and small state helpers.

Fortify owns login, logout, and password-reset controllers. The application provides its Blade views and active-user authentication callback. Profile and password changes use the project layers instead of Fortify's optional update endpoints.

## Authorization

The sidebar and Blade `@can` directives improve usability, but route middleware, FormRequest authorization, and Gate checks enforce security. The `super-admin` Gate override grants operational permissions while explicitly denying entry to the Admin, Instructor, and Trainee portals. Other application code checks permissions rather than role names. Ownership and subject foreign-key comparisons normalize persisted identifiers to integers before strict comparison so authorization is consistent across database drivers and PDO casting modes.

## Data ownership

- `config/permissions.php` owns permission names.
- `config/admin-menu.php` owns the four independent portal navigation manifests.
- Services own role synchronization and audit event properties.
- Repositories own filtering and pagination.
- Credit awards are claimable ledger records. Fiscal-year configuration, attendance snapshots, and awards use dedicated repositories and services; claimed totals are derived from claimed ledger rows.

## LMS modules

The portals use separate routes, portal transport controllers, dashboards, and menu manifests while sharing domain services, repository contracts, Eloquent repositories, and models. This keeps role experiences independent without duplicating course, enrollment, assessment, and reporting rules. Standalone quizzes and course assessments are separate bounded models: quizzes use direct assignments, while course assessments belong only to course learning materials and gate required sequential progress. Uploaded learning files use authorized download routes rather than public storage. See `docs/lms.md` for the domain model and operational details.

Attendance is isolated behind `AttendanceProviderInterface`. The current sandbox provider supplies deterministic local data, while a future TMIS HTTP implementation can be added without moving external API concerns into controllers or credit-award persistence.

# Learning Management System

## Delivered MVP

The application now includes a complete server-rendered LMS workflow under the existing `/admin` Fortify boundary. All users authenticate through the same login and receive a permission-filtered dashboard and sidebar.

The code-owned system roles are:

- `super-admin`: all configured permissions through the existing Gate override.
- `admin`: trainee/instructor account administration, enrollment assignment, global course and assessment visibility, results, and reports.
- `instructor`: owned course/curriculum authoring, assessment authoring, publishing, assignment, and owned results.
- `trainee`: assigned learning, material completion/downloads, assessment attempts, and own results.

Run `php artisan admin:permissions-sync` whenever permissions or the default matrices in `config/lms.php` change. The command exact-syncs permission definitions and resets the three non-super system roles to their code-owned defaults. Run `php artisan admin:menu-regenerate` after route or menu changes.

## Domain model

```text
CourseCategory
  -> Course
       -> CourseModule
            -> LearningMaterial
                 -> optional Assessment

User -> Enrollment -> MaterialProgress

Assessment
  -> AssessmentQuestion
       -> QuestionOption
  -> AssessmentAssignment
  -> AssessmentAttempt
       -> AttemptAnswer
```

Assessments are independent records. They may remain standalone, link to a course/final assessment, link to a module, and/or appear as a learning material. This avoids coupling the assessment engine to course delivery and leaves room for a future question bank.

## Course authoring

Courses support draft, published, and archived states; beginner/intermediate/advanced difficulty; estimated duration; thumbnail; category; instructor ownership; and free or sequential navigation. A course must have at least one module containing material before publication.

Modules and materials use persisted integer positions. The current UI exposes move-up/move-down controls. Drag-and-drop can later call the same service/repository boundary.

Supported materials are article, video URL/upload, PDF, PPT/PPTX, DOC/DOCX, external link, downloadable file, and assessment. Uploaded learning files are stored on the private local disk and are served only through an authorized enrollment download route. Thumbnail images use the public disk. Article HTML is reduced to a small safe tag set and stripped of attributes before storage.

## Enrollment and learning

The MVP uses administrator assignment. Assignment is idempotent for each trainee/course pair and reactivates a cancelled record. Trainees only see their own non-cancelled enrollments.

Opening a material records the last view and starts the enrollment. Completing required materials recalculates progress as:

```text
completed required materials / total required materials * 100
```

At 100 percent, the enrollment becomes completed and receives a completion timestamp. Sequential courses reject access when an earlier required material is incomplete. Assessment materials are completed automatically after a passing attempt and cannot be manually completed before passing.

## Assessments and results

Assessments support draft, published, and closed states; duration; passing percentage; maximum attempts; availability dates; course/module links; and standalone trainee assignment. Questions support single choice, multiple choice, and true/false behavior using reusable option rows.

Starting a test creates or resumes one in-progress attempt and records its expiry. Submission compares selected option IDs with the complete correct option set, awards marks only for exact matches, calculates percentage, stores each answer, and records pass/fail. Graded attempts are immutable through the application UI.

## Reporting

The dashboard adapts to effective permissions:

- administrators see users, courses, enrollment, completion, and graded-attempt metrics;
- instructors see owned courses, enrollment, assessments, and recent results;
- trainees see assigned learning, completion, and passed-test metrics.

The reports page contains basic course completion, trainee completion, and assessment pass/fail/average-score reports.

## Local demo data

`php artisan db:seed` creates local-only demonstration data after permissions are synchronized. It does not run the demo seeder in testing or production.

When `LMS_DEMO_LOGIN_ENABLED=true`, the login screen displays one-click buttons for all four accounts. The setting defaults to enabled for `APP_ENV=local` and disabled elsewhere. Keep it disabled in production and any environment exposed to untrusted users. If a seeded user is missing or inactive, its shortcut refuses authentication.

| Account | Password | Access |
| --- | --- | --- |
| `superadmin@example.com` | `Password123!` | Super Admin |
| `lms.admin@example.com` | `Password123!` | Admin |
| `instructor@example.com` | `Password123!` | Instructor |
| `trainee@example.com` | `Password123!` | Trainee |

The existing bootstrap `admin@admin.com` behavior is unchanged. Change all predictable local credentials before exposing an environment.

## Architecture decisions and future work

The root specification describes user-specific permissions, but this repository explicitly prohibits direct user permissions without a separate architecture decision. This implementation therefore uses fixed role permissions only. A future exception would require authorization documentation, override semantics, administration safeguards, and tests before enabling Spatie's direct user assignment.

SSO/OIDC, TMIS synchronization, certificates, notifications, SCORM/xAPI, live classes, assignments, manual grading, question banks, randomized exams, and exports remain future phases. The LMS tables reference local user IDs only at the authentication boundary, so a future stable TMIS/OIDC subject mapping can be introduced without changing course, learning, or assessment rules.

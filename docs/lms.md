# Learning Management System

## Delivered MVP

The application includes a complete server-rendered LMS workflow with one shared Fortify login and four separate portals. Each user has exactly one fixed system role and receives that portal's dashboard, route namespace, and menu instead of a combined feature list.

The code-owned system roles are:

- `super-admin` (`/super-admin`): system oversight, fixed-role account administration, global academic override, reports, access matrix, and activity log. It does not expose learner functions.
- `admin` (`/admin`): Instructor/Trainee account administration, course application review, direct enrollment, read-only global academic visibility, results, and reports.
- `instructor` (`/instructor`): owned course/curriculum and assessment authoring, owned-course application review, related trainee progress, assignment, and owned results.
- `trainee` (`/learning`): published course catalog and applications, approved learning, material completion/downloads, assessment attempts, and own results.

Run `php artisan admin:permissions-sync` whenever permissions or the default matrices in `config/lms.php` change. The command exact-syncs permission definitions and resets the three non-super system roles to their code-owned defaults. Run `php artisan admin:menu-regenerate` after route or menu changes.

## Domain model

```text
CourseCategory
  -> Course
       -> CourseModule
            -> CourseChapter
                 -> LearningMaterial
                      -> optional CourseAssessment
                           -> CourseAssessmentQuestion
                                -> CourseAssessmentOption

User -> Enrollment -> MaterialProgress

Enrollment pending -> active/completed
                   -> rejected -> pending on reapplication

Assessment
  -> AssessmentQuestion
       -> QuestionOption
  -> AssessmentAssignment
  -> AssessmentAttempt
       -> AttemptAnswer
```

`Assessment` is the standalone quiz engine. It has no course, module, or learning-material relationship and is available only through direct trainee assignments. `CourseAssessment` is a separate course-material model with its own questions, options, attempts, and answers.

## Course authoring

Courses support draft, published, and archived states; beginner/intermediate/advanced difficulty; estimated duration; optional thumbnail; category; instructor ownership; and free or sequential navigation. Before publication, a course must have a title, description, at least one module, every module must have a chapter, and every chapter must contain learning material. The instructor editor shows module/chapter/material/question counts and a publishing-readiness summary with links to each blocker.

Modules, chapters, and materials use persisted integer positions. New modules receive an editable `Chapter 1`, every published module and chapter must contain content, and non-empty chapters cannot be deleted. Module and chapter creation uses dedicated modal forms triggered by the bottom Add buttons; the shared modal backdrop dims the page without blur and closes on outside click or Escape. The authoring UI presents modules and chapters as independently collapsible accordions; focusing a target module or chapter closes sibling panels, completeness links expand and scroll to empty chapters, and URL anchors reopen the relevant panel after material or modal edits. Authorized authors can drag modules within a course, chapters within their current module, and materials within their chapter; all drops autosave through validated service/repository boundaries. Materials display dynamic `Page 1`, `Page 2`, and similar labels that update after reordering.

Learning materials are created and edited on dedicated authoring pages. The form shows only fields relevant to the selected material type and provides a live, read-only trainee-style preview beside the form. Video materials play inside the enrolled course player: YouTube URLs use an embedded YouTube player, while uploaded videos use an authorized native video player backed by the private local disk. The enrolled learning player displays the full Module -> Chapter -> Material hierarchy; the public catalog keeps its compact module-level material list.

Supported new materials are article, video URL/upload, file, and course assessment. External-link and legacy records remain supported for compatibility. Course assessments allow only single-choice and multiple-choice questions, require one question before course publishing, use a configurable passing percentage, allow unlimited retakes, and show selected/correct answers after submission. Course-assessment question editing remains available until the first trainee attempt. Uploaded learning files and rich-text images are stored on the private local disk and are served only through authorized routes. Rich-text images are tracked per learning material, limited to JPEG, PNG, and WebP files up to 5 MB, and removed when no longer referenced. Thumbnail images use the public disk. Article and material-note HTML is reduced to a small safe tag set, protected image sources are retained, and unsafe attributes or external image URLs are stripped before storage. A deployment migration normalizes legacy materials that already have a course-assessment record but an incorrect material type.

## Applications, enrollment, and learning

Published courses appear in the Trainee catalog. Applying creates a pending enrollment record but does not grant course materials or quiz access. An Admin can review any pending application; an Instructor can review only applications for courses they own. Approval activates the enrollment, while rejection stores an optional review note. A rejected or cancelled application can be submitted again. Duplicate pending applications and applications for already active/completed enrollment are rejected.

Instructor and Admin application review groups requests inside course-level collapsible panels with plus/dash controls, applied and accepted totals, hover states, and ascending request dates. Approved requests leave the review list and appear in the Instructor My Trainees view, grouped by course with the same collapsible presentation.

Admin and Super Admin retain direct assignment. Assignment is idempotent for each trainee/course pair and supersedes pending, rejected, or cancelled state. Trainees see only active and completed enrollments in My Learning; pending/rejected/cancelled records remain in My Applications.

Opening a material records the last view and starts the enrollment. A sequential-course request for a locked lesson returns a course-state screen identifying the blocking lesson, showing completed required items, and offering both the blocking item and course-contents recovery actions instead of a raw authorization error. Completing required materials recalculates progress as:

```text
completed required materials / total required materials * 100
```

Learner-facing progress uses one shared course-item summary. Required course items include the course assessment, while the supporting lesson metric excludes it and reports learning materials separately. Enrolled-course cards, catalog detail, the course player, assessment result, and completed-course summary therefore use the same numerator, denominator, percentage, assessment state, and next-action decision. Incomplete launches resume the most recently viewed unfinished required item; completed launches open a review summary, with an explicit start-from-beginning action and up to four compact suggestions for other available courses. Regular lessons are completed automatically when the learner advances to the next item, so the player does not require a manual completion action on every lesson. A course assessment is a knowledge check within the same course sequence, not a final exam: it can start only after earlier required items are complete, and a passing attempt continues to the next unfinished course item or the completed-course summary. When the last required item is completed, the player shows a completion dialog linking to the course summary.

At 100 percent, the enrollment becomes completed and receives a completion timestamp. Sequential courses reject access when an earlier required material is incomplete. Required course-assessment materials are completed automatically after a passing attempt and cannot be manually completed before passing. Failed attempts can be retaken without a limit.

## Assessments and results

Standalone quizzes support draft, published, and closed states; duration; passing percentage; maximum attempts; availability dates; training restrictions; and trainee applications or direct assignment. Questions support single choice, multiple choice, and true/false behavior using reusable option rows. They are not course materials. A trainee may apply from the published test catalog; Admin/Super Admin reviewers can approve any eligible applicant, while instructors can approve applicants for tests they own. Approval creates the existing assessment assignment, preserving assignment due-date and attempt rules.

Starting a standalone quiz creates or resumes one in-progress attempt and records its integer-cast expiry duration. Course-assessment attempts are separate, unlimited, and choice-only. While a trainee works, answers are autosaved to the attempt and locally recovered in the browser. Submission compares selected option IDs with the complete correct option set, awards marks only for exact matches, calculates percentage, stores each answer, and records pass/fail. Submission is idempotent so a retry after a lost browser response returns the existing result rather than creating duplicate answers; the submission transaction locks the attempt row while grading. The player disables the submit action, warns about unanswered questions, confirms the final action, displays saving/submitting/recovery states, and redirects through an explicit JSON success URL. Course-assessment results include score, percentage, passing score, attempt date, and selected/correct options. Graded attempts are immutable through the application UI.

The application provides friendly 403 and 500 screens with an explanation and recovery actions. Operational Super Admins may use the protected `admin.maintenance.optimize-clear` and `admin.maintenance.migrate` POST routes when command-line access is unavailable.

Assessment submission now uses an in-page confirmation modal so trainees can review unanswered questions before the final request.

## Reporting

Each portal has a separate dashboard query and view:

- Super Admin sees system-wide users, courses, applications, enrollments, completion, and recent activity;
- Admin sees operational users, pending applications, active learning, completion, and assessment results;
- Instructor sees owned courses, owned-course applications, related trainees, assessments, and recent results;
- Trainee sees pending applications, approved learning, completion, and passed-test metrics.

The reports page contains basic course completion, trainee completion, and assessment pass/fail/average-score reports.

## Fiscal-year credit scores

Super Admin manages explicit fiscal-year periods with draft, active, and closed states. Periods cannot overlap and only one period may be active. Each fiscal year defines the attendance threshold and fixed attendance credit value.

Courses, standalone assessments, and course assessments may each define credit points. A completed course or passed test creates one eligible credit award for the learner and fiscal year. Awards are idempotent per source activity and remain eligible until the trainee claims them. Claimed totals are calculated from the credit ledger rather than stored as a mutable balance.

The trainee Fiscal Year Credit Score page shows the current attendance snapshot, for example `36 / 90`, eligible awards, claimed history, and total claimed credits. Credit-bearing course cards and completed catalog course details show the configured course credit value before completion and expose the claim action once the completion award is eligible; the assessment result and completed-course summary repeat the amount and claim prompt; completed enrollments from before credit awards were introduced can recover and claim their missing idempotent course award; claimed awards are shown as claimed, including a **Credit claimed** badge on enrolled course cards. Attendance is refreshed through an `AttendanceProviderInterface`; the default local implementation is a sandbox provider configured through `TMIS_SANDBOX_PRESENT_DAYS`. A future TMIS REST provider can replace the adapter without changing the credit ledger.

The trainee navbar displays the active fiscal year, claimed total, and eligible credits ready to claim, while the dashboard highlights unclaimed awards. These values are calculated on demand and are not persisted notifications.

Admins and Super Admins can open the **Credit Score Viewer** from their portal navigation. It defaults to the active fiscal year and supports fiscal-year selection and trainee search. The summary table shows course, quiz, overall, and claimed/ready totals for every active trainee. Opening a trainee provides three read-only tabs: **Overall** combines all awards and the attendance snapshot, **Courses** lists course activity and completion awards, and **Quizzes** lists standalone and course-embedded quiz attempts with pass results and credit awarded.

The trainee portal uses a focused top navigation instead of the authenticated sidebar. Its tabs are **Course**, **My Courses**, **Tests**, **My Tests**, and a disabled **Feedback** placeholder. Course opens the overview and links to the catalog; My Courses shows active and completed enrollments; Tests opens the published test catalog with search, test details, category browsing, and an application action; and My Tests shows every test related to the trainee (pending applications, assignments, attempts, results, and retained unavailable history) with status filters and state-appropriate actions. Browsing a published test does not grant permission to take it: approval or a direct assignment is required. Feedback has no route or workflow yet and is intentionally non-interactive.

The trainee sidebar contains **Overview**, a **Courses** group, a **Tests** group, and **Credit Scores**. Courses contains Course Catalog, Applied Courses, and Enrolled Courses. Tests contains Test Catalog and **My Tests**; the catalog exposes published tests and category browsing, while My Tests includes pending applications, approved assignments, in-progress and reviewed attempts, rejected applications, and retained unavailable history. The My Tests screen is presented as **Tests & Assessments**, with search, status filters, recent/name sorting, assignment due dates, attempt counts, scores, and state-appropriate start, retry, and result actions. Credit Scores is shared by both learning types and separates Course Credit Scores from Test Credit Scores, showing the source module, points, training eligibility, enrollment/attempt progress, pass state, and claim status.

Staff have a separate **Test Applications** queue. Admins and Super Admins can review all applications; instructors can review applications for tests they own. Approval and rejection are transactional, record reviewer and note metadata, and are exposed through permission-protected routes. The legacy Applied Tests URL remains available as a compatibility view for assigned tests that have not started.

Starting or continuing an enrolled course opens the dedicated course player in a new tab. Learning URLs are enrollment-scoped (`/learning/enrollments/{enrollment}/...`) so an enrollment ID cannot be mistaken for a course ID. The player uses a focused learning layout without the portal sidebar or regular navbar, and provides its own course outline, progress indicator, material content, completion controls, assessment actions, and previous/next lesson navigation. Course-assessment attempts and results stay inside this same learning layout and course outline. The focused course layout does not display the portal locale switcher. My Learning only includes active or completed enrollments whose course is still published; stale assignments to draft courses are not exposed or playable.

Course-assessment materials open with an assessment overview before an attempt starts. The overview shows the course breadcrumb, assessment type and time metadata, learner-facing instructions, question count, passing score, unlimited-attempt policy, current status, and links to start or read the instructions. The overview uses the same course progress header and outline as the rest of the focused learning player.

My Courses supports server-side search by course title, progress-state filters for all, in-progress, completed, and not-started enrollments, and sorting by recently added or course name. The compact cards preserve the shared progress summary and credit-score claim state while linking to the course player or completed-course summary.

Instructor course authoring includes a full-course trainee preview, a completeness checklist, chapter material counts, module progress indicators, expand/collapse-all controls with remembered state, and direct links from publishing blockers to incomplete course assessments. Course assessment authoring shares the choice-question editor with standalone quizzes. Course assessments require one question before publishing; question editing locks after the first started attempt, while standalone quiz questions lock when the quiz is published or has attempt history. Instructor trainee management consolidates course progress with assigned quizzes, due dates, attempts, scores, and completion state.

### Training-restricted content

Courses and standalone assessments can be marked as available to everyone or restricted to one required training. The authoring form uses a fixed training catalog from `config/training.php`, and the sandbox enrollment provider uses the same file's user-to-training mapping. Trainee catalog, enrollment, learning, assessment listing, and assessment-start workflows enforce the restriction server-side. Course-embedded assessments inherit their parent course restriction.

The provider interfaces under `App\Services\Training` are intentionally separate from the content models so the configuration providers can later be replaced by TMIS catalog and enrollment API adapters without changing course or assessment records.

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

The agreed backlog for reusable test schedules and history, academic ownership and collaboration, and scoped Admin access is recorded in the [Future LMS Roadmap](future-lms-roadmap.md). These behaviors are documentation-only and are not part of the delivered MVP.

SSO/OIDC, live TMIS synchronization, TMIS training enrollment mapping, certificates, persisted notifications, SCORM/xAPI, live classes, assignments, manual grading, question banks, randomized exams, and exports remain future phases. The LMS tables reference local user IDs only at the authentication boundary, so a future stable TMIS/OIDC subject mapping can be introduced without changing course, learning, or assessment rules.

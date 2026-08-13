# Future LMS Roadmap

This document records agreed backlog items for a later LMS phase. It describes intended product behavior only; none of these items are part of the delivered MVP until they are implemented, documented in the main LMS guide, and covered by permissions and tests.

## Reusable tests, schedules, and history

- Separate a reusable test definition from its scheduled runs. Questions and core scoring rules belong to versioned test definitions; dates, participants, attempt limits, result release, status, and reports belong to individual runs.
- Preserve immutable test versions so completed and active runs always retain the questions and scoring rules used at the time. Edits create a version for future runs instead of rewriting history.
- Support one-off schedules, a **Run again** action prefilled from an earlier run, and daily, weekly, or monthly recurrence. Recurrence supports an interval, local start time, selected weekdays where applicable, and either an end date or occurrence count.
- Use an explicit trainee roster for each run. A recurring schedule has a template roster that is copied to future occurrences. For a course-linked test, only trainees actively or previously enrolled in that course are eligible for selection.
- Allow changes to a scheduled run until it opens. At its opening time, freeze its test version, settings, and participant roster. Later recurrence edits affect only occurrences that have not opened.
- Keep every occurrence and attempt as history rather than resetting prior results. Authorized staff can view participation, attempt counts, latest and best scores, pass rate, and average score, with participant- and attempt-level CSV export.
- Let trainees view only their own scheduled-run history and attempts: schedule status and dates, attempt count, score, pass/fail result, and answer review when released. They must never see another trainee's data.
- Configure result release per run as immediate after submission, after the run closes, or hidden. Answer review follows the same release rule.

## Academic ownership and collaboration

- Courses and reusable tests have one permanent owner, which may be an Admin or Instructor. Admins may create their own courses and tests; those records remain under that Admin's ownership.
- Manage course collaborators and test collaborators separately. Linking a test to a course does not implicitly grant permission to edit the test definition.
- A course collaborator may manage curriculum, publishing, applications, and other normal course operations without becoming its owner. Existing role restrictions still apply: an Instructor collaborator may approve or reject applications but may not directly enroll trainees.
- Only the owner or Super Admin may add or remove collaborators, transfer ownership, or delete the record. Ownership may be transferred to another Admin or Instructor without changing the historical creator/audit record.
- A collaboration grant never bypasses a restricted Admin's allowed-instructor scope. Instructor-to-Instructor collaboration remains available within the applicable course or test rules.

## Administrative scope and assignment

- Super Admin manages Admin accounts and retains global academic override, including the ability to assign any published course or scheduled test to eligible trainees.
- Each Admin defaults to access to all Instructors. Super Admin may restrict an Admin to a selected Instructor list.
- Apply an Admin's Instructor scope consistently to Instructor accounts and their courses, applications, enrollments, tests, scheduled runs, results, reports, and related trainees. Do not implement this as direct Spatie permissions on individual users.
- Admins manage only Instructors in their scope and Trainees accessible through those Instructors' academic records. A trainee created by an Admin initially remains manageable by that creator; other restricted Admins gain access through an eligible course relationship.
- When a restricted Admin creates an Instructor, automatically add that Instructor to the Admin's allowed list. Only Super Admin may manage Admin accounts.
- Admins may directly enroll trainees only in published courses they own, courses owned by an allowed Instructor, or delegated courses whose owner is in scope. Instructors may review applications for courses they own or collaborate on, but cannot directly enroll trainees.
- Admins may assign scheduled tests they own or may manage within their allowed scope. Instructors may assign their own or delegated scheduled tests only to eligible trainees. Every course and test participant selector must return trainee-role users only; Admin, Instructor, and Super Admin accounts can never be enrolled as learners or assigned as test participants.

## Implementation guardrails

- Implement each feature through the repository's required `Route -> FormRequest -> Controller -> Service -> Repository contract -> Eloquent repository -> Model` flow.
- Introduce explicit code-owned permissions, policies/authorization checks, activity events, menu entries, migrations and backfills before exposing these workflows.
- Scope repository queries as well as navigation and selectors; hiding controls is not an authorization boundary.
- Add Pest coverage for ownership, collaboration, Admin scope, trainee-only participant validation, schedule freezing, recurrence generation, version history, result release, report visibility, and CSV authorization.
- Update this roadmap and `docs/lms.md` when work moves from the backlog into the delivered application.

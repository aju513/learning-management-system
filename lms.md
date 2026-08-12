# Learning Management System (LMS) — MVP Functional Flow

## 1. Purpose

This document defines the surface-level functional flow and system logic for the Learning Management System (LMS) MVP.

The LMS is being developed alongside the existing **TMIS** system.

For the MVP:

- The LMS will use the **existing Laravel Fortify authentication** already present in the application.
- Users will log in using the normal application login.
- Access will be differentiated using **roles and permissions**.
- No separate SSO flow is required for the first MVP.
- TMIS-to-LMS SSO can be introduced later without changing the core LMS domain.

The LMS should provide a Coursera-like learning experience with:

- Course creation
- Modules
- Learning materials
- Videos
- Articles
- PDF files
- PPT/PPTX files
- DOC/DOCX files
- External learning resources
- Tests and quizzes
- Progress tracking
- Course enrollment
- Test attempts and results
- Role-based administration

---

# 2. High-Level Architecture

For the MVP, the system can be treated logically as:

```text
Existing Application / TMIS
        │
        ├── Laravel Fortify Authentication
        │
        └── LMS Module
              │
              ├── User & Access Management
              ├── Course Management
              ├── Learning Materials
              ├── Enrollment
              ├── Learning Progress
              ├── Assessment
              ├── Results
              └── Reporting
```

Later, the LMS can be separated or integrated through SSO:

```text
TMIS
  │
  │ OIDC / SSO
  ▼
LMS
```

For now, this is not required.

---

# 3. Authentication Strategy — MVP

The MVP will continue using the existing **Laravel Fortify login**.

```text
User
  ↓
Login Page
  ↓
Fortify Authentication
  ↓
Credentials Valid?
  ↓
Identify User Role
  ↓
Check Permissions
  ↓
Redirect to Role-Specific Dashboard
```

Fortify remains responsible for:

- Login
- Logout
- Password handling
- Password reset
- Session management
- Existing authentication security
- Any existing 2FA flow, if enabled

The LMS should not replace Fortify authentication.

---

# 4. Roles

The LMS will initially support four roles:

1. Super Admin
2. Admin
3. Instructor
4. Trainee

The role determines the user's default access level.

---

# 5. Permission Strategy

Permissions in the LMS will be **predefined and fixed**.

The system does not need a feature that allows admins to create arbitrary new permission names.

Examples of fixed permissions:

```text
USER_VIEW
USER_CREATE
USER_EDIT
USER_DISABLE

ADMIN_MANAGE
INSTRUCTOR_MANAGE
TRAINEE_MANAGE

COURSE_VIEW
COURSE_CREATE
COURSE_EDIT
COURSE_DELETE
COURSE_PUBLISH

MODULE_CREATE
MODULE_EDIT
MODULE_DELETE
MODULE_REORDER

CONTENT_CREATE
CONTENT_EDIT
CONTENT_DELETE
CONTENT_REORDER

TEST_VIEW
TEST_CREATE
TEST_EDIT
TEST_DELETE
TEST_PUBLISH

ENROLLMENT_VIEW
ENROLLMENT_MANAGE

RESULT_VIEW
RESULT_EXPORT

REPORT_VIEW
```

The system should support two permission levels:

```text
Role Permissions
      +
User-Specific Permissions
```

Example:

```text
Role: Instructor

Default permissions:
✓ View courses
✓ Create courses
✓ Edit own courses
✓ Create tests
✓ View trainee results

Specific Instructor:
+ Can publish courses
+ Can manage a particular course
```

A user's effective authorization should be calculated from:

```text
Role
+
Assigned permissions
+
Any user-specific overrides
```

The exact technical implementation can be decided during development, but the functional behavior should follow this model.

---

# 6. Role Responsibilities

## 6.1 Super Admin

Super Admin has full LMS control.

Main responsibilities:

- Manage Admin users
- Manage Instructor users
- Manage Trainee users
- Assign roles
- Assign fixed permissions
- Assign user-specific permissions
- View all courses
- Create or modify any course if necessary
- Publish or unpublish courses
- Manage tests
- Manage enrollment
- View all results
- View all reports
- Manage LMS configuration

Suggested navigation:

```text
Dashboard

User Management
 ├── Admins
 ├── Instructors
 └── Trainees

Learning
 ├── Courses
 ├── Categories
 └── Enrollments

Assessments
 ├── Tests
 └── Results

Reports

Roles & Permissions

Settings
```

---

## 6.2 Admin

Admin handles day-to-day administrative LMS operations.

Main responsibilities:

- Manage trainees
- Manage instructors if permission is granted
- View courses
- Manage course enrollments
- Assign trainees to courses
- View tests
- View test results
- View completion reports
- Generate or export reports if permitted

The Admin role should primarily focus on administration rather than academic content creation.

Suggested navigation:

```text
Dashboard

Trainees

Instructors

Courses

Enrollments

Assessments
 ├── Tests
 └── Results

Reports
```

---

## 6.3 Instructor

Instructor is responsible for learning content and assessments.

Main responsibilities:

- Create courses
- Edit own courses
- Create modules
- Add learning materials
- Reorder course content
- Create tests
- Add questions
- Configure test rules
- Publish courses if permission is granted
- View trainees enrolled in their courses
- View test results
- View course completion status

Suggested navigation:

```text
Dashboard

My Courses

Tests

Trainees

Results
```

---

## 6.4 Trainee

Trainee is the learner.

Main responsibilities:

- View assigned/enrolled courses
- Open course content
- Complete learning materials
- Watch videos
- Read articles
- View/download learning files
- Take quizzes
- Take standalone tests
- View results
- Track learning progress
- View completed courses
- View certificates later if certificate functionality is introduced

Suggested navigation:

```text
Dashboard

My Learning

Browse Courses

Tests

Results

Certificates
```

Certificates may be excluded from the first MVP.

---

# 7. Main LMS Domains

The LMS should be divided conceptually into the following modules:

```text
LMS
│
├── Identity & Access
│     ├── Authentication
│     ├── Roles
│     └── Permissions
│
├── User Management
│     ├── Super Admin
│     ├── Admin
│     ├── Instructor
│     └── Trainee
│
├── Course Management
│     ├── Course
│     ├── Module
│     └── Learning Material
│
├── Enrollment
│
├── Learning
│     ├── Course Player
│     └── Progress
│
├── Assessment
│     ├── Test
│     ├── Questions
│     ├── Attempts
│     └── Results
│
├── Reporting
│
├── Notifications
│
└── Future TMIS Integration
      └── SSO / OIDC
```

---

# 8. Course Structure

The LMS should follow this hierarchy:

```text
Course
 ├── Module 1
 │    ├── Learning Material
 │    ├── Learning Material
 │    ├── Learning Material
 │    └── Quiz / Test
 │
 ├── Module 2
 │    ├── Learning Material
 │    ├── Learning Material
 │    └── Quiz / Test
 │
 └── Final Assessment
```

Example:

```text
Course:
Introduction to Local Governance

Module 1:
Introduction

    Video
    ↓
    Article
    ↓
    PDF
    ↓
    Quiz

Module 2:
Planning

    PPT
    ↓
    Video
    ↓
    Document
    ↓
    Quiz

Final Assessment
```

A course may contain any number of modules.

A module may contain any number of learning materials.

---

# 9. Course Lifecycle

The MVP should use simple course states:

```text
DRAFT
PUBLISHED
ARCHIVED
```

### DRAFT

- Visible to authorized staff
- Editable
- Not available to trainees

### PUBLISHED

- Available to enrolled trainees
- Can be used for active learning

### ARCHIVED

- No longer actively offered
- Historical progress/results remain available

---

# 10. Course Creation Flow

Instructor or authorized administrator:

```text
Create Course
     ↓
Enter Course Details
     ↓
Build Curriculum
     ↓
Add Modules
     ↓
Add Learning Materials
     ↓
Add Assessments
     ↓
Configure Course Settings
     ↓
Preview
     ↓
Publish
```

Suggested flow:

## Step 1 — Course Details

Fields may include:

- Course title
- Short description
- Full description
- Thumbnail
- Category
- Instructor
- Difficulty level
- Estimated duration

## Step 2 — Curriculum

Create modules.

Example:

```text
Module 1 — Introduction

Module 2 — Planning

Module 3 — Implementation
```

## Step 3 — Add Learning Materials

Each module can contain multiple learning items.

## Step 4 — Course Settings

Possible settings:

- Enrollment mode
- Completion criteria
- Sequential/free learning mode
- Final passing requirements

## Step 5 — Preview

Instructor reviews the trainee experience.

## Step 6 — Publish

Course becomes accessible to enrolled trainees.

---

# 11. Learning Material Model

Learning content should be treated as a general concept called:

```text
Learning Material
```

Each learning material has a type.

MVP material types:

```text
ARTICLE
VIDEO
PDF
PPT
PPTX
DOC
DOCX
EXTERNAL_LINK
DOWNLOADABLE_FILE
QUIZ / TEST
```

This makes the LMS extensible.

Future types may include:

```text
SCORM
LIVE_CLASS
ASSIGNMENT
SURVEY
AUDIO
INTERACTIVE_CONTENT
```

These are not required for MVP.

---

# 12. Learning Material Creation Flow

Instructor selects:

```text
Add Material
    ↓
Choose Material Type
```

Possible options:

```text
Article
Video
PDF
Document
Presentation
External Link
Test
```

The form changes according to type.

### Article

```text
Title
Content
Optional description
```

### Video

```text
Title
Upload video or enter supported video URL
Optional description
```

### PDF

```text
Title
Upload PDF
Optional description
```

### PPT / PPTX

```text
Title
Upload presentation file
Optional description
```

### DOC / DOCX

```text
Title
Upload document
Optional description
```

### External Link

```text
Title
URL
Optional description
```

### Test

Attach an existing test or create one from the Assessment module.

---

# 13. Module Management

An instructor should be able to:

- Create module
- Rename module
- Edit module
- Delete module
- Reorder modules
- Add learning materials
- Reorder learning materials

Example:

```text
Course: Local Governance

☰ Module 1 — Introduction

    ☰ Introduction Video       Video
    ☰ Basic Concepts           Article
    ☰ Reference Guidelines     PDF
    ☰ Module Quiz              Test

    + Add Material

☰ Module 2 — Planning

+ Add Module
```

Drag-and-drop ordering can be used in the UI.

---

# 14. Enrollment

Enrollment represents the relationship between a trainee and a course.

Conceptually:

```text
Trainee
   ↕
Enrollment
   ↕
Course
```

MVP enrollment methods:

### Admin Assignment

```text
Admin
  ↓
Select Trainee(s)
  ↓
Select Course
  ↓
Assign
```

### Optional Self-Enrollment

If enabled:

```text
Trainee
  ↓
Browse Courses
  ↓
Select Published Course
  ↓
Enroll
```

For the first MVP, **Admin assignment is sufficient**.

---

# 15. Trainee Course Flow

```text
Login
  ↓
Trainee Dashboard
  ↓
My Learning
  ↓
Select Course
  ↓
Course Overview
  ↓
Start / Continue
  ↓
Open Module
  ↓
Complete Learning Material
  ↓
Continue to Next Material
  ↓
Complete Module Quiz
  ↓
Continue to Next Module
  ↓
Final Assessment
  ↓
Course Completion
```

Suggested trainee course page:

```text
--------------------------------------------------
Introduction to Local Governance
55% Complete
--------------------------------------------------

Curriculum                 Content

✓ Module 1                 Introduction to ...
   ✓ Introduction
   ✓ Video                 [Current Content]
   ✓ Article
   ✓ Quiz

▶ Module 2
   ○ Planning
   ○ Document
   ○ Video
   ○ Quiz

🔒 Module 3
--------------------------------------------------
```

The trainee learning interface should include:

- Curriculum/sidebar
- Current learning content
- Progress
- Previous button
- Next button

---

# 16. Learning Progress

Progress should be tracked at three levels:

```text
Learning Material
Module
Course
```

Example:

```text
Course: 55% Completed

Module 1 ✓
Module 2 70%
Module 3 0%
```

A module may show:

```text
✓ Article
✓ Video
○ PDF
○ Quiz
```

The course progress can be calculated from completed learning items.

The exact percentage formula may be finalized during implementation.

---

# 17. Learning Navigation Modes

Courses may support two modes.

## Free Navigation

The trainee may open any available material.

```text
Module 1
 ├── Video
 ├── Article
 ├── PDF
 └── Quiz
```

## Sequential Navigation

The trainee must complete materials in order.

```text
Material 1
    ↓ complete
Material 2
    ↓ complete
Quiz
    ↓ pass
Module 2 unlocks
```

Sequential mode is useful for mandatory government training.

For MVP, this can either be implemented as a simple course option or deferred if time is limited.

---

# 18. Assessment Module

Tests should be treated as an independent LMS domain.

Do not design tests so they only exist inside courses.

This allows the same system to support:

```text
Course Quiz
Module Test
Final Assessment
Standalone Test
```

Conceptually:

```text
Assessment Management
│
├── Tests
├── Questions
├── Attempts
└── Results
```

---

# 19. Test Lifecycle

Suggested states:

```text
DRAFT
PUBLISHED
CLOSED
```

### DRAFT

Test is still editable.

### PUBLISHED

Test is available to assigned trainees or courses.

### CLOSED

No additional attempts are allowed.

---

# 20. Test Creation Flow

Instructor:

```text
Tests
  ↓
Create Test
  ↓
Enter Test Details
  ↓
Add Questions
  ↓
Configure Rules
  ↓
Preview
  ↓
Publish
```

Test settings may include:

- Title
- Description
- Instructions
- Duration
- Passing marks
- Maximum attempts
- Start date
- End date
- Result visibility

---

# 21. Question Types

For MVP, start with automatically gradable questions:

```text
SINGLE_CHOICE
MULTIPLE_CHOICE
TRUE_FALSE
```

Later:

```text
SHORT_ANSWER
LONG_ANSWER
FILE_UPLOAD
ASSIGNMENT
```

Manual grading can be introduced later.

---

# 22. Question Creation

Example:

```text
Question

Which level of government is responsible for ...?

○ Option A
○ Option B
● Option C
○ Option D

Marks: 2

Correct Answer:
Option C
```

Instructor functionality:

- Add question
- Edit question
- Delete question
- Reorder questions
- Set marks
- Select correct answer

---

# 23. Question Bank

A reusable Question Bank is useful but not mandatory for MVP.

Future model:

```text
Question Bank
     ↓
Categories
     ↓
Questions
     ↓
Reuse in multiple tests
```

For the first MVP, instructors may create questions directly inside tests.

The assessment architecture should still allow a Question Bank to be added later.

---

# 24. Course Tests

Tests can be attached to:

```text
Course
 ├── Module Quiz
 ├── Module Test
 └── Final Assessment
```

Example:

```text
Course
  ↓
Module 1
  ↓
Learning Materials
  ↓
Module Quiz
```

A course may also have:

```text
Final Assessment
```

at the end of all modules.

---

# 25. Standalone Tests

Tests may also exist outside courses.

Flow:

```text
Instructor Creates Test
        ↓
Admin / Instructor Assigns Test
        ↓
Trainee Dashboard
        ↓
Assigned Tests
        ↓
Take Test
```

This is useful for exams, competency tests, entrance tests, or assessments that do not require a complete course.

---

# 26. Test-Taking Flow

Trainee:

```text
Dashboard
   ↓
Tests
   ↓
Select Test
   ↓
Read Instructions
   ↓
Start Test
   ↓
Timer Starts
   ↓
Answer Questions
   ↓
Submit
   ↓
System Grades Objective Questions
   ↓
Result
```

Example test instruction screen:

```text
Duration: 30 minutes
Questions: 25
Passing Score: 60%
Maximum Attempts: 2
```

After submission:

```text
Score
Pass / Fail
Attempt Number
Completion Time
```

Optional later settings:

```text
Show score immediately
Show correct answers
Show results after deadline
Allow answer review
```

---

# 27. Test Attempts

Each test may allow one or multiple attempts.

Examples:

```text
Maximum Attempts: 1
```

or

```text
Maximum Attempts: 3
```

A trainee attempt may have:

```text
NOT_STARTED
IN_PROGRESS
SUBMITTED
GRADED
```

If the test is auto-graded, `SUBMITTED` may immediately become `GRADED`.

---

# 28. Test Result Logic

For objective questions:

```text
Trainee submits test
        ↓
System checks answers
        ↓
Calculate earned marks
        ↓
Calculate score percentage
        ↓
Compare against passing score
        ↓
PASS or FAIL
```

Example:

```text
Total Marks: 50
Obtained Marks: 38

Score = 76%

Passing Score = 60%

Result = PASS
```

---

# 29. Course Completion

A course should only be considered completed when its configured completion rules are satisfied.

Example:

```text
All required learning materials completed
        +
Required quizzes completed
        +
Final assessment passed
        ↓
Course Completed
```

For simpler MVP implementation:

```text
All required materials complete
+
Final test passed
```

may be sufficient.

---

# 30. Dashboard Logic

## Super Admin Dashboard

Suggested metrics:

- Total admins
- Total instructors
- Total trainees
- Total courses
- Published courses
- Active enrollments
- Completed courses
- Tests conducted
- Pass rate

---

## Admin Dashboard

Suggested metrics:

- Total trainees
- Active courses
- Active enrollments
- Course completion
- Tests conducted
- Pass rate

---

## Instructor Dashboard

Suggested metrics:

- My courses
- Published courses
- Enrolled trainees
- Average completion
- Active tests
- Recent results

---

## Trainee Dashboard

Suggested metrics:

- Enrolled courses
- Courses in progress
- Completed courses
- Upcoming tests
- Recent results
- Certificates later

---

# 31. Notifications

Basic events worth supporting:

```text
Course assigned
Course published
Test assigned
Test deadline approaching
Test submitted
Result published
Course completed
```

For MVP, in-app notifications are sufficient.

Possible future notification channels:

```text
Email
SMS
Push Notification
```

---

# 32. Reporting

MVP reports can remain simple.

Suggested reports:

### Course Report

- Course name
- Number of enrolled trainees
- Number completed
- Number in progress
- Completion percentage

### Trainee Report

- Trainee
- Enrolled courses
- Completed courses
- Current progress
- Test results

### Test Report

- Test name
- Number of attempts
- Pass count
- Fail count
- Average score

Advanced analytics are not required for MVP.

---

# 33. Certificates

Certificates may be introduced later.

Future flow:

```text
Course Completed
       ↓
Completion Rules Satisfied
       ↓
Generate Certificate
       ↓
Trainee Can View / Download
```

Certificate generation is not required for the initial MVP unless it becomes a mandatory project requirement.

---

# 34. Current Login Routing

All users use the existing Fortify login.

```text
Login
  ↓
Fortify validates credentials
  ↓
Load authenticated user
  ↓
Determine role
  ↓
Determine permissions
  ↓
Redirect
```

Example redirects:

```text
SUPER_ADMIN
    ↓
/super-admin/dashboard

ADMIN
    ↓
/admin/dashboard

INSTRUCTOR
    ↓
/instructor/dashboard

TRAINEE
    ↓
/learning/dashboard
```

A shared dashboard implementation is also possible as long as visible features are determined by role and permissions.

---

# 35. Authorization Rules

Authentication and authorization should remain separate concepts.

```text
Authentication
=
Who are you?
```

Fortify handles this.

```text
Authorization
=
What are you allowed to do?
```

Roles and permissions handle this.

Example:

```text
Instructor tries to edit Course A

Authenticated?
   ↓ YES

Has COURSE_EDIT?
   ↓ YES

Owns Course A or has elevated permission?
   ↓ YES

Allow
```

Another example:

```text
Admin tries to publish Course A

Authenticated?
   ↓ YES

Role = Admin

Has COURSE_PUBLISH?
   ↓ NO

Reject
```

Routes and server-side actions must enforce authorization.

The frontend should also hide actions the user cannot perform, but frontend visibility must never be the only security layer.

---

# 36. Ownership Rules

Where applicable, actions should consider resource ownership.

Example:

```text
Instructor A
  ↓
May edit Course A owned by Instructor A
```

but:

```text
Instructor A
  ↓
Cannot automatically edit Course B owned by Instructor B
```

unless a specific permission allows it.

Super Admin can bypass normal ownership restrictions.

Admin behavior depends on assigned permissions.

---

# 37. Suggested MVP Scope

The MVP should include:

## Authentication

- Existing Fortify login
- Logout
- Role-based routing

## Authorization

- Four roles
- Fixed permissions
- Role permissions
- User-specific permissions

## User Management

- Super Admin management
- Admin management
- Instructor management
- Trainee management
- Activate/deactivate accounts

## Course Management

- Create course
- Edit course
- Draft/published/archived states
- Modules
- Module ordering

## Learning Materials

- Article
- Video
- PDF
- DOC/DOCX
- PPT/PPTX
- External link
- Downloadable file

## Enrollment

- Assign trainee to course
- View trainee enrollments

## Learning

- Course player
- Previous/next navigation
- Material completion
- Module progress
- Course progress

## Assessment

- Create test
- Edit test
- Single-choice questions
- Multiple-choice questions
- True/false questions
- Timer
- Passing score
- Maximum attempts
- Auto grading

## Results

- Attempt history
- Score
- Pass/fail
- Course progress
- Completion status

## Reporting

- Basic course reports
- Basic trainee reports
- Basic test reports

---

# 38. Out of Scope for Initial MVP

Avoid adding these unless required:

```text
SCORM
xAPI
Live classes
Assignments
Discussion forums
Chat
Gamification
Badges
Advanced certificate designer
AI course generation
AI grading
Advanced recommendation engine
Complex prerequisites
Dynamic permission creation
Advanced question pools
Randomized exams
Offline learning
Mobile application
Complex TMIS synchronization
Multi-tenant organizations
```

These can be future phases.

---

# 39. Future TMIS SSO Integration

The future architecture should allow TMIS to become the identity provider.

The existing LMS domain should not depend on Fortify-specific assumptions beyond authentication.

Future flow:

```text
Trainee Opens LMS
       ↓
Login with TMIS
       ↓
Redirect to TMIS
       ↓
TMIS authenticates trainee
       ↓
TMIS issues authorization code
       ↓
LMS exchanges code
       ↓
LMS verifies signed identity token
       ↓
LMS identifies trainee
       ↓
Trainee enters LMS
```

Recommended protocol:

```text
OpenID Connect
Authorization Code Flow
PKCE
state
nonce
```

TMIS may remain responsible for its existing Fortify login.

Conceptually:

```text
Fortify
=
TMIS local authentication

OIDC
=
TMIS-to-LMS identity delegation
```

---

# 40. Future Identity Mapping

When SSO is introduced, TMIS should provide a stable external identifier.

Example identity token claims:

```json
{
  "sub": "TMIS-USER-18273",
  "name": "Example User",
  "email": "user@example.com"
}
```

The LMS should use `sub` as the stable external identity.

Do not use email as the permanent SSO identity key because email can change.

Conceptually:

```text
TMIS User
   ↓
OIDC sub
   ↓
LMS Trainee Identity
```

---

# 41. Future TMIS Data Integration

Later, TMIS may provide information such as:

```text
Employee profile
Department
Office
Province
Local level
Designation
Employment information
Training eligibility
```

Recommended direction:

```text
TMIS
  │
  │ API / OIDC
  ▼
LMS
```

Avoid making the LMS directly dependent on TMIS database tables.

---

# 42. Future LMS-to-TMIS Synchronization

The LMS may later send training results back to TMIS.

Possible synchronization data:

```text
Course completed
Completion date
Training hours
Assessment score
Pass / Fail
Certificate information
```

Conceptually:

```text
LMS
  ↓
Completion / Results API
  ↓
TMIS
```

This will allow TMIS to remain the master government personnel/training information system while the LMS handles actual course delivery.

---

# 43. System Boundary

The long-term conceptual responsibility should remain:

```text
TMIS
=
Identity
Employee / trainee master information
Organizational information
Training records
```

```text
LMS
=
Course delivery
Learning content
Assessment
Progress
Results
```

Later:

```text
TMIS ─── SSO ─── LMS
  ▲                │
  └──── Results ───┘
```

For MVP, both may still live inside the same broader Laravel project or infrastructure.

---

# 44. Recommended Development Order

A practical development sequence:

## Phase 1 — Identity & Access

```text
Fortify login
Roles
Permissions
Role-based routing
Authorization middleware / policies
```

## Phase 2 — User Management

```text
Super Admin
Admin
Instructor
Trainee management
```

## Phase 3 — Course Management

```text
Course CRUD
Course lifecycle
Modules
Module ordering
```

## Phase 4 — Learning Materials

```text
Articles
Videos
PDF
DOC/DOCX
PPT/PPTX
Links
Material ordering
```

## Phase 5 — Enrollment

```text
Assign trainees
View enrollments
```

## Phase 6 — Learning Experience

```text
Course player
Material completion
Module progress
Course progress
```

## Phase 7 — Assessments

```text
Tests
Questions
Rules
Attempts
Auto grading
Results
```

## Phase 8 — Reporting

```text
Course reports
Trainee reports
Test reports
```

## Phase 9 — Future TMIS Integration

```text
OIDC / SSO
External identity mapping
TMIS APIs
LMS result synchronization
```

---

# 45. Complete User Flow

## Super Admin

```text
Login
  ↓
Fortify
  ↓
Super Admin Dashboard
  ↓
Manage Users
  ↓
Assign Roles / Permissions
  ↓
Monitor Courses
  ↓
Monitor Enrollments
  ↓
Monitor Tests
  ↓
View Reports
```

---

## Admin

```text
Login
  ↓
Fortify
  ↓
Admin Dashboard
  ↓
Manage Trainees
  ↓
Manage Enrollments
  ↓
View Courses
  ↓
View Tests
  ↓
View Results
  ↓
Reports
```

---

## Instructor

```text
Login
  ↓
Fortify
  ↓
Instructor Dashboard
  ↓
Create Course
  ↓
Create Modules
  ↓
Add Learning Materials
  ↓
Create Tests
  ↓
Preview
  ↓
Publish
  ↓
Trainees Learn
  ↓
View Progress & Results
```

---

## Trainee

```text
Login
  ↓
Fortify
  ↓
Trainee Dashboard
  ↓
My Learning
  ↓
Open Assigned Course
  ↓
Complete Modules
  ↓
Complete Learning Materials
  ↓
Take Quizzes
  ↓
Take Final Assessment
  ↓
Pass
  ↓
Course Completed
```

---

# 46. Complete Course Flow

```text
Instructor
   ↓
Create Course
   ↓
Add Course Details
   ↓
Create Modules
   ↓
Add Learning Materials
   ↓
Add Tests
   ↓
Configure Course
   ↓
Preview
   ↓
Publish
   ↓
Admin Assigns Trainees
   ↓
Trainees Start Course
   ↓
Progress Recorded
   ↓
Assessments Completed
   ↓
Course Completion Calculated
   ↓
Results Available
```

---

# 47. Complete Assessment Flow

```text
Instructor
   ↓
Create Test
   ↓
Configure Duration / Passing Score / Attempts
   ↓
Add Questions
   ↓
Set Correct Answers
   ↓
Publish
   ↓
Attach to Course or Assign Standalone
   ↓
Trainee Starts Attempt
   ↓
Answer Questions
   ↓
Submit
   ↓
Auto Grade
   ↓
Calculate Score
   ↓
PASS / FAIL
   ↓
Store Result
   ↓
Instructor/Admin Can View Result
```

---

# 48. MVP Design Principles

The LMS should follow these principles:

### Keep Authentication Simple

Continue using existing Fortify authentication for MVP.

### Separate Authentication From Authorization

Fortify identifies the user.

Roles and permissions determine access.

### Keep Permissions Fixed

Do not create a complex permission-generation system.

### Keep Courses Modular

```text
Course
→ Module
→ Learning Material
```

### Keep Assessments Independent

Tests should work both inside and outside courses.

### Track Progress From the Beginning

Progress is a core LMS feature, not an optional analytics feature.

### Avoid Tight TMIS Coupling

The LMS should not require direct TMIS database access for every operation.

### Build for Future SSO

Even though the MVP uses Fortify, avoid designing LMS logic around the assumption that every trainee must always authenticate locally.

### Keep MVP Small

Do not reproduce all of Coursera.

Implement the core learning workflow first.

---

# 49. Final MVP Flow Summary

```text
                       EXISTING APPLICATION
                              │
                          Fortify Login
                              │
                     Role + Permission Check
                              │
            ┌─────────────────┼──────────────────┐
            │                 │                  │
       Super Admin          Admin            Instructor
            │                 │                  │
       Manage All        Manage Users       Create Courses
       Permissions       Enrollments        Create Tests
       Reports           Reports            View Results
            │                 │                  │
            └─────────────────┼──────────────────┘
                              │
                          LMS Content
                              │
                              ▼
                           Trainee
                              │
                      Assigned Courses
                              │
                              ▼
                            Course
                              │
                  ┌───────────┴───────────┐
                  │                       │
                Module                  Module
                  │                       │
            Learning Material       Learning Material
                  │                       │
        Video / Article / Docs           Test
                  │                       │
                  └───────────┬───────────┘
                              │
                           Progress
                              │
                       Final Assessment
                              │
                       PASS / FAIL
                              │
                         Completion
                              │
                           Reports
```

---

# 50. Future Target Architecture

After the MVP is stable:

```text
                     TMIS
                      │
               Fortify Login
                      │
                OIDC Provider
                      │
                      │ SSO
                      ▼
                     LMS
                      │
        ┌─────────────┼─────────────┐
        │             │             │
      Courses      Learning      Assessment
        │             │             │
        └─────────────┼─────────────┘
                      │
                    Results
                      │
                      ▼
                     TMIS
```

The first version therefore focuses on building the LMS correctly while keeping a clean migration path toward proper TMIS SSO integration later.

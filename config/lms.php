<?php

return [
    'course_assessment_min_questions' => 1,

    'demo_login' => [
        'enabled' => env('LMS_DEMO_LOGIN_ENABLED', env('APP_ENV') === 'local'),
        'accounts' => [
            'super-admin' => [
                'label' => 'Super Admin',
                'email' => 'superadmin@example.com',
            ],
            'admin' => [
                'label' => 'Admin',
                'email' => 'lms.admin@example.com',
            ],
            'instructor' => [
                'label' => 'Instructor',
                'email' => 'instructor@example.com',
            ],
            'trainee' => [
                'label' => 'Trainee',
                'email' => 'trainee@example.com',
            ],
        ],
    ],
    'roles' => [
        'admin' => [
            'portals.admin.access', 'dashboard.view',
            'users.manage', 'users.show', 'users.create', 'users.edit', 'users.delete', 'users.change-status', 'users.assign-roles',
            'users.manage-instructors', 'users.manage-trainees',
            'courses.manage', 'courses.show', 'courses.view-all',
            'course-categories.manage', 'course-categories.create', 'course-categories.edit', 'course-categories.delete',
            'assessment-categories.manage', 'assessment-categories.create', 'assessment-categories.edit', 'assessment-categories.delete',
            'fiscal-years.manage', 'fiscal-years.show', 'fiscal-years.create', 'fiscal-years.edit', 'fiscal-years.delete',
            'enrollments.manage', 'enrollments.create', 'enrollments.delete',
            'course-applications.review-all',
            'assessments.manage', 'assessments.show', 'assessments.view-all', 'assessments.create', 'assessments.edit', 'assessments.edit-any',
            'assessments.delete', 'assessments.publish', 'assessments.assign', 'assessments.import', 'assessments.reorder',
            'results.manage', 'results.view-all', 'results.grade-any', 'reports.view',
            'credit-scores.view-all',
        ],
        'instructor' => [
            'portals.instructor.access', 'dashboard.view',
            'courses.manage', 'courses.show', 'courses.create', 'courses.edit', 'courses.delete', 'courses.publish',
            'modules.create', 'modules.edit', 'modules.delete', 'modules.reorder',
            'chapters.create', 'chapters.edit', 'chapters.delete', 'chapters.reorder',
            'materials.create', 'materials.edit', 'materials.delete', 'materials.reorder',
            'course-assessments.questions.manage',
            'course-applications.review-owned', 'course-progress.view-owned',
            'assessments.manage', 'assessments.show', 'assessments.create', 'assessments.edit', 'assessments.delete', 'assessments.publish', 'assessments.assign', 'assessments.import', 'assessments.reorder',
            'results.manage', 'results.view-owned', 'results.grade-owned',
        ],
        'trainee' => [
            'portals.trainee.access', 'dashboard.view',
            'course-catalog.view', 'course-applications.view-own', 'course-applications.create',
            'learning.view', 'learning.complete', 'learning.download',
            'assessments.take', 'results.manage',
            'credit-scores.view-own', 'credit-scores.claim-own', 'credit-scores.refresh-attendance',
        ],
    ],
    'role_management_permissions' => [
        'admin' => 'users.manage-admins',
        'instructor' => 'users.manage-instructors',
        'trainee' => 'users.manage-trainees',
    ],
];

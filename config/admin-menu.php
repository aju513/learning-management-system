<?php

return [
    'super-admin' => [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'super-admin.dashboard', 'permission' => 'portals.super-admin.access', 'order' => 10],
        ['key' => 'users', 'label' => 'User Management', 'icon' => 'users', 'order' => 20, 'children' => [
            ['key' => 'admins', 'label' => 'Admins', 'route' => 'super-admin.admins.index', 'permission' => 'users.manage-admins', 'order' => 10],
            ['key' => 'instructors', 'label' => 'Instructors', 'route' => 'super-admin.instructors.index', 'permission' => 'users.manage-instructors', 'order' => 20],
            ['key' => 'trainees', 'label' => 'Trainees', 'route' => 'super-admin.trainees.index', 'permission' => 'users.manage-trainees', 'order' => 30],
        ]],
        ['key' => 'course-overview', 'label' => 'Course Overview', 'icon' => 'dashboard', 'order' => 30, 'children' => [
            ['key' => 'courses', 'label' => 'Courses', 'route' => 'super-admin.courses.index', 'permission' => 'courses.manage', 'order' => 10],
            ['key' => 'applications', 'label' => 'Applications', 'route' => 'super-admin.applications.index', 'permission' => 'course-applications.review-all', 'order' => 20],
            ['key' => 'enrollments', 'label' => 'Enrollments', 'route' => 'super-admin.enrollments.index', 'permission' => 'enrollments.manage', 'order' => 30],
        ]],
        ['key' => 'test-overview', 'label' => 'Test Overview', 'icon' => 'permissions', 'order' => 40, 'children' => [
            ['key' => 'tests', 'label' => 'Quizzes', 'route' => 'super-admin.assessments.index', 'permission' => 'assessments.manage', 'order' => 10],
            ['key' => 'results', 'label' => 'Results', 'route' => 'super-admin.results.index', 'permission' => 'results.manage', 'order' => 20],
        ]],
        ['key' => 'system-settings', 'label' => 'System Settings', 'icon' => 'system', 'order' => 50, 'children' => [
            ['key' => 'fiscal-years', 'label' => 'Fiscal Years', 'route' => 'super-admin.fiscal-years.index', 'permission' => 'fiscal-years.manage', 'order' => 10],
            ['key' => 'categories', 'label' => 'Categories', 'route' => 'super-admin.course-categories.index', 'permission' => 'course-categories.manage', 'order' => 20],
        ]],
        ['key' => 'reports', 'label' => 'Reports', 'icon' => 'activity-log', 'route' => 'super-admin.reports.index', 'permission' => 'reports.view', 'order' => 60],
        ['key' => 'activity', 'label' => 'Activity Log', 'icon' => 'activity-log', 'route' => 'super-admin.activity.index', 'permission' => 'activity-log.view', 'order' => 70],
    ],
    'admin' => [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'admin.dashboard', 'permission' => 'portals.admin.access', 'order' => 10],
        ['key' => 'people', 'label' => 'People', 'icon' => 'users', 'order' => 20, 'children' => [
            ['key' => 'instructors', 'label' => 'Instructors', 'route' => 'admin.instructors.index', 'permission' => 'users.manage-instructors', 'order' => 10],
            ['key' => 'trainees', 'label' => 'Trainees', 'route' => 'admin.trainees.index', 'permission' => 'users.manage-trainees', 'order' => 20],
        ]],
        ['key' => 'courses', 'label' => 'Courses', 'icon' => 'dashboard', 'route' => 'admin.courses.index', 'permission' => 'courses.manage', 'order' => 30],
        ['key' => 'applications', 'label' => 'Applications', 'icon' => 'users', 'route' => 'admin.applications.index', 'permission' => 'course-applications.review-all', 'order' => 40],
        ['key' => 'enrollments', 'label' => 'Enrollments', 'icon' => 'users', 'route' => 'admin.enrollments.index', 'permission' => 'enrollments.manage', 'order' => 50],
        ['key' => 'assessments', 'label' => 'Assessments', 'icon' => 'permissions', 'order' => 60, 'children' => [
            ['key' => 'tests', 'label' => 'Quizzes', 'route' => 'admin.assessments.index', 'permission' => 'assessments.manage', 'order' => 10],
            ['key' => 'results', 'label' => 'Results', 'route' => 'admin.results.index', 'permission' => 'results.manage', 'order' => 20],
        ]],
        ['key' => 'reports', 'label' => 'Reports', 'icon' => 'activity-log', 'route' => 'admin.reports.index', 'permission' => 'reports.view', 'order' => 70],
    ],
    'instructor' => [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'instructor.dashboard', 'permission' => 'portals.instructor.access', 'order' => 10],
        ['key' => 'courses', 'label' => 'My Courses', 'icon' => 'journal-bookmark', 'route' => 'instructor.courses.index', 'permission' => 'courses.manage', 'order' => 20],
        ['key' => 'applications', 'label' => 'Applications', 'icon' => 'users', 'route' => 'instructor.applications.index', 'permission' => 'course-applications.review-owned', 'order' => 30],
        ['key' => 'trainees', 'label' => 'My Trainees', 'icon' => 'users', 'route' => 'instructor.trainees.index', 'permission' => 'course-progress.view-owned', 'order' => 40],
        ['key' => 'assessments', 'label' => 'My Quizzes', 'icon' => 'pencil', 'route' => 'instructor.assessments.index', 'permission' => 'assessments.manage', 'order' => 50],
        ['key' => 'results', 'label' => 'Results', 'icon' => 'activity-log', 'route' => 'instructor.results.index', 'permission' => 'results.manage', 'order' => 60],
    ],
    'trainee' => [
        ['key' => 'overview', 'label' => 'Overview', 'icon' => 'dashboard', 'route' => 'learning.dashboard', 'permission' => 'portals.trainee.access', 'order' => 10],
        ['key' => 'courses', 'label' => 'Courses', 'icon' => 'journal-bookmark', 'order' => 20, 'children' => [
            ['key' => 'course-catalog', 'label' => 'Course Catalog', 'route' => 'learning.catalog.index', 'permission' => 'course-catalog.view', 'order' => 10],
            ['key' => 'applied-courses', 'label' => 'Applied Courses', 'route' => 'learning.applications.index', 'permission' => 'course-applications.view-own', 'order' => 20],
            ['key' => 'enrolled-courses', 'label' => 'Enrolled Courses', 'route' => 'learning.courses.index', 'permission' => 'learning.view', 'order' => 30],
        ]],
        ['key' => 'tests', 'label' => 'Tests', 'icon' => 'permissions', 'order' => 30, 'children' => [
            ['key' => 'applied-tests', 'label' => 'Applied Tests', 'route' => 'learning.assessments.applied', 'permission' => 'assessments.take', 'order' => 10],
            ['key' => 'enrolled-tests', 'label' => 'Enrolled Tests', 'route' => 'learning.assessments.index', 'permission' => 'assessments.take', 'order' => 20],
        ]],
        ['key' => 'credit-scores', 'label' => 'Credit Scores', 'icon' => 'activity-log', 'route' => 'learning.credit-scores.index', 'permission' => 'credit-scores.view-own', 'order' => 40],
    ],
];

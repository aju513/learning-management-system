<?php

return [
    'portals' => [
        'portals.super-admin.access' => [
            'view_title' => 'Access the Super Admin portal',
            'description' => 'Allows access to the dedicated Super Admin portal.',
        ],
        'portals.admin.access' => [
            'view_title' => 'Access the Admin portal',
            'description' => 'Allows access to the dedicated operational Admin portal.',
        ],
        'portals.instructor.access' => [
            'view_title' => 'Access the Instructor portal',
            'description' => 'Allows access to the dedicated Instructor portal.',
        ],
        'portals.trainee.access' => [
            'view_title' => 'Access the Trainee portal',
            'description' => 'Allows access to the dedicated Trainee learning portal.',
        ],
    ],
    'dashboard' => [
        'dashboard.view' => [
            'view_title' => 'View dashboard',
            'description' => 'Allows access to the main administration dashboard.',
        ],
    ],
    'users' => [
        'users.manage' => [
            'view_title' => 'Manage users',
            'description' => 'Allows access to the users index, filters, and pagination.',
        ],
        'users.show' => [
            'view_title' => 'View users',
            'description' => 'Allows viewing individual user details.',
        ],
        'users.create' => [
            'view_title' => 'Create users',
            'description' => 'Allows creating new user accounts.',
        ],
        'users.edit' => [
            'view_title' => 'Edit users',
            'description' => 'Allows updating user account details.',
        ],
        'users.delete' => [
            'view_title' => 'Delete users',
            'description' => 'Allows permanently deleting user accounts.',
        ],
        'users.change-status' => [
            'view_title' => 'Change user status',
            'description' => 'Allows activating or deactivating user accounts.',
        ],
        'users.assign-roles' => [
            'view_title' => 'Assign user roles',
            'description' => 'Allows assigning roles while creating or editing users.',
        ],
        'users.manage-admins' => [
            'view_title' => 'Manage administrator accounts',
            'description' => 'Allows assigning and managing administrator roles.',
        ],
        'users.manage-instructors' => [
            'view_title' => 'Manage instructor accounts',
            'description' => 'Allows assigning and managing instructor roles.',
        ],
        'users.manage-trainees' => [
            'view_title' => 'Manage trainee accounts',
            'description' => 'Allows assigning and managing trainee roles.',
        ],
    ],
    'course-categories' => [
        'course-categories.manage' => ['view_title' => 'Manage course categories', 'description' => 'Allows listing and filtering course categories.'],
        'course-categories.create' => ['view_title' => 'Create course categories', 'description' => 'Allows creating course categories.'],
        'course-categories.edit' => ['view_title' => 'Edit course categories', 'description' => 'Allows editing course categories.'],
        'course-categories.delete' => ['view_title' => 'Delete course categories', 'description' => 'Allows deleting empty course categories.'],
    ],
    'courses' => [
        'courses.manage' => ['view_title' => 'Manage courses', 'description' => 'Allows access to the course management list.'],
        'courses.show' => ['view_title' => 'View courses', 'description' => 'Allows viewing course details and curriculum.'],
        'courses.view-all' => ['view_title' => 'View all courses', 'description' => 'Allows viewing courses owned by any instructor.'],
        'courses.create' => ['view_title' => 'Create courses', 'description' => 'Allows creating new courses.'],
        'courses.edit' => ['view_title' => 'Edit owned courses', 'description' => 'Allows editing courses owned by the user.'],
        'courses.edit-any' => ['view_title' => 'Edit any course', 'description' => 'Allows editing courses owned by any instructor.'],
        'courses.delete' => ['view_title' => 'Delete owned courses', 'description' => 'Allows deleting owned courses without enrollment history.'],
        'courses.publish' => ['view_title' => 'Publish courses', 'description' => 'Allows publishing, archiving, and returning owned courses to draft.'],
    ],
    'modules' => [
        'modules.create' => ['view_title' => 'Create course modules', 'description' => 'Allows adding modules to editable courses.'],
        'modules.edit' => ['view_title' => 'Edit course modules', 'description' => 'Allows editing modules in editable courses.'],
        'modules.delete' => ['view_title' => 'Delete course modules', 'description' => 'Allows deleting modules from editable courses.'],
        'modules.reorder' => ['view_title' => 'Reorder course modules', 'description' => 'Allows changing module order in editable courses.'],
    ],
    'chapters' => [
        'chapters.create' => ['view_title' => 'Create course chapters', 'description' => 'Allows adding chapters to editable course modules.'],
        'chapters.edit' => ['view_title' => 'Edit course chapters', 'description' => 'Allows editing chapters in editable courses.'],
        'chapters.delete' => ['view_title' => 'Delete course chapters', 'description' => 'Allows deleting empty chapters from editable courses.'],
        'chapters.reorder' => ['view_title' => 'Reorder course chapters', 'description' => 'Allows changing chapter order within a course module.'],
    ],
    'materials' => [
        'materials.create' => ['view_title' => 'Create learning materials', 'description' => 'Allows adding learning materials to course modules.'],
        'materials.edit' => ['view_title' => 'Edit learning materials', 'description' => 'Allows editing learning materials.'],
        'materials.delete' => ['view_title' => 'Delete learning materials', 'description' => 'Allows deleting learning materials.'],
        'materials.reorder' => ['view_title' => 'Reorder learning materials', 'description' => 'Allows changing learning material order.'],
    ],
    'enrollments' => [
        'enrollments.manage' => ['view_title' => 'Manage enrollments', 'description' => 'Allows viewing course enrollment records.'],
        'enrollments.create' => ['view_title' => 'Assign enrollments', 'description' => 'Allows assigning trainees to published courses.'],
        'enrollments.delete' => ['view_title' => 'Cancel enrollments', 'description' => 'Allows cancelling trainee course enrollments.'],
    ],
    'course-applications' => [
        'course-catalog.view' => ['view_title' => 'Browse published courses', 'description' => 'Allows trainees to browse the published course catalog.'],
        'course-applications.view-own' => ['view_title' => 'View own course applications', 'description' => 'Allows trainees to view their own course application history.'],
        'course-applications.create' => ['view_title' => 'Apply for courses', 'description' => 'Allows trainees to apply for published courses.'],
        'course-applications.review-all' => ['view_title' => 'Review all course applications', 'description' => 'Allows reviewing applications for every course.'],
        'course-applications.review-owned' => ['view_title' => 'Review owned-course applications', 'description' => 'Allows instructors to review applications for courses they own.'],
    ],
    'course-progress' => [
        'course-progress.view-owned' => ['view_title' => 'View owned-course trainee progress', 'description' => 'Allows instructors to view trainees and progress for courses they own.'],
    ],
    'learning' => [
        'learning.view' => ['view_title' => 'Access learning', 'description' => 'Allows viewing courses assigned to the current user.'],
        'learning.complete' => ['view_title' => 'Complete learning materials', 'description' => 'Allows recording completion of assigned learning materials.'],
        'learning.download' => ['view_title' => 'Download learning files', 'description' => 'Allows downloading files from assigned courses.'],
    ],
    'assessments' => [
        'assessments.manage' => ['view_title' => 'Manage assessments', 'description' => 'Allows access to the assessment management list.'],
        'assessments.show' => ['view_title' => 'View assessments', 'description' => 'Allows viewing assessment details and questions.'],
        'assessments.view-all' => ['view_title' => 'View all assessments', 'description' => 'Allows viewing assessments created by any instructor.'],
        'assessments.create' => ['view_title' => 'Create quizzes', 'description' => 'Allows creating standalone quizzes.'],
        'assessments.edit' => ['view_title' => 'Edit owned assessments', 'description' => 'Allows editing assessments created by the user.'],
        'assessments.edit-any' => ['view_title' => 'Edit any assessment', 'description' => 'Allows editing assessments created by any instructor.'],
        'assessments.delete' => ['view_title' => 'Delete assessments', 'description' => 'Allows deleting owned assessments without attempts.'],
        'assessments.publish' => ['view_title' => 'Publish assessments', 'description' => 'Allows publishing and closing owned assessments.'],
        'assessments.assign' => ['view_title' => 'Assign assessments', 'description' => 'Allows assigning standalone assessments to trainees.'],
        'assessments.import' => ['view_title' => 'Import quiz questions', 'description' => 'Allows importing questions from the approved XLSX template.'],
        'assessments.reorder' => ['view_title' => 'Reorder quiz questions', 'description' => 'Allows changing question order in editable quizzes.'],
        'assessments.take' => ['view_title' => 'Take assessments', 'description' => 'Allows starting and submitting assigned assessments.'],
    ],
    'results' => [
        'results.manage' => ['view_title' => 'View results', 'description' => 'Allows access to assessment attempt results.'],
        'results.view-owned' => ['view_title' => 'View owned assessment results', 'description' => 'Allows viewing results for assessments created by the user.'],
        'results.view-all' => ['view_title' => 'View all results', 'description' => 'Allows viewing all trainee assessment results.'],
        'results.grade-owned' => ['view_title' => 'Grade owned quiz responses', 'description' => 'Allows manually grading question-and-answer responses for owned quizzes.'],
        'results.grade-any' => ['view_title' => 'Grade any quiz response', 'description' => 'Allows manually grading question-and-answer responses for any quiz.'],
    ],
    'reports' => [
        'reports.view' => ['view_title' => 'View LMS reports', 'description' => 'Allows viewing course, trainee, and assessment reports.'],
    ],
    'system' => [
        'permissions.view' => [
            'view_title' => 'View permissions',
            'description' => 'Allows viewing the configured permission catalog.',
        ],
        'activity-log.view' => [
            'view_title' => 'View activity log',
            'description' => 'Allows viewing administrative and security activity records.',
        ],
    ],
];

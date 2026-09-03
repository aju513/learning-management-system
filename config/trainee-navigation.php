<?php

return [
    'tabs' => [
        [
            'key' => 'course',
            'label' => 'Course',
            'route' => 'learning.dashboard',
            'permission' => 'course-catalog.view',
            'active' => ['learning.dashboard', 'learning.catalog.*', 'learning.applications.*'],
        ],
        [
            'key' => 'my-courses',
            'label' => 'My Courses',
            'route' => 'learning.courses.index',
            'permission' => 'learning.view',
            'active' => ['learning.courses.*'],
        ],
        [
            'key' => 'tests',
            'label' => 'Tests',
            'route' => 'learning.assessments.catalog',
            'permission' => 'test-catalog.view',
            'active' => ['learning.assessments.catalog*'],
        ],
        [
            'key' => 'my-tests',
            'label' => 'My Tests',
            'route' => 'learning.assessments.index',
            'permission' => 'test-applications.view-own',
            'active' => ['learning.assessments.index', 'learning.assessments.show', 'learning.assessments.attempts.*', 'learning.results.*'],
        ],
        [
            'key' => 'feedback',
            'label' => 'Feedback',
            'disabled' => true,
            'active' => [],
        ],
    ],
];

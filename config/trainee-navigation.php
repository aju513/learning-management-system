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
            'route' => 'learning.assessments.applied',
            'permission' => 'assessments.take',
            'active' => ['learning.assessments.applied'],
        ],
        [
            'key' => 'my-tests',
            'label' => 'My Tests',
            'route' => 'learning.assessments.index',
            'permission' => 'assessments.take',
            'active' => ['learning.assessments.index', 'learning.assessments.attempts.*', 'learning.results.*'],
        ],
        [
            'key' => 'feedback',
            'label' => 'Feedback',
            'disabled' => true,
            'active' => [],
        ],
    ],
];

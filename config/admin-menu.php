<?php

return [
    [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'icon' => 'dashboard',
        'route' => 'admin.dashboard',
        'permission' => 'dashboard.view',
        'order' => 10,
    ],
    [
        'key' => 'access',
        'label' => 'Access Control',
        'icon' => 'access-control',
        'order' => 20,
        'children' => [
            ['key' => 'users', 'label' => 'Users', 'icon' => 'users', 'route' => 'admin.users.index', 'permission' => 'users.manage', 'order' => 10],
            ['key' => 'roles', 'label' => 'Roles', 'icon' => 'roles', 'route' => 'admin.roles.index', 'permission' => 'roles.manage', 'order' => 20],
            ['key' => 'permissions', 'label' => 'Permissions', 'icon' => 'permissions', 'route' => 'admin.permissions.index', 'permission' => 'permissions.view', 'order' => 30],
        ],
    ],
    [
        'key' => 'system',
        'label' => 'System',
        'icon' => 'system',
        'order' => 30,
        'children' => [
            ['key' => 'activity-log', 'label' => 'Activity Log', 'icon' => 'activity-log', 'route' => 'admin.activity.index', 'permission' => 'activity-log.view', 'order' => 10],
            ['key' => 'ui-kit', 'label' => 'UI Kit', 'icon' => 'ui-kit', 'route' => 'admin.ui-kit', 'permission' => 'ui-kit.view', 'order' => 20],
        ],
    ],
];

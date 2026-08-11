<?php

return [
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
    ],
    'roles' => [
        'roles.manage' => [
            'view_title' => 'Manage roles',
            'description' => 'Allows access to the roles index, filters, and pagination.',
        ],
        'roles.show' => [
            'view_title' => 'View roles',
            'description' => 'Allows viewing role permissions and assigned users.',
        ],
        'roles.create' => [
            'view_title' => 'Create roles',
            'description' => 'Allows creating roles and selecting their permissions.',
        ],
        'roles.edit' => [
            'view_title' => 'Edit roles',
            'description' => 'Allows changing role names and assigned permissions.',
        ],
        'roles.delete' => [
            'view_title' => 'Delete roles',
            'description' => 'Allows deleting roles that have no assigned users.',
        ],
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
        'ui-kit.view' => [
            'view_title' => 'View UI kit',
            'description' => 'Allows viewing the protected TailAdmin component catalog.',
        ],
    ],
];

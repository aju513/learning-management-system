# Permissions and Menus

## Permission catalog

`config/permissions.php` returns groups of stable dot-separated permission names with administrator-facing metadata. Every permission defines a `view_title` (the display text) and a `description` explaining what access it grants. These fields are synchronized to the permissions table as well. Add a permission to that file before using it in routes, FormRequests, Blade, or navigation.

CRUD features use five independent abilities: `<feature>.manage`, `<feature>.show`, `<feature>.create`, `<feature>.edit`, and `<feature>.delete`. `manage` protects the index/filter page and sidebar entry; the other abilities protect their matching page, route, and action button. Permissions are never inferred from hidden navigation or from another CRUD ability.

For example:

```php
'products' => [
    'products.manage' => [
        'view_title' => 'Manage products',
        'description' => 'Allows access to the product index, filters, and pagination.',
    ],
    'products.show' => [
        'view_title' => 'View products',
        'description' => 'Allows viewing individual product details.',
    ],
    'products.create' => [
        'view_title' => 'Create products',
        'description' => 'Allows creating new products.',
    ],
    'products.edit' => [
        'view_title' => 'Edit products',
        'description' => 'Allows updating product details.',
    ],
    'products.delete' => [
        'view_title' => 'Delete products',
        'description' => 'Allows deleting products.',
    ],
],
```

Run:

```bash
php artisan admin:permissions-sync
```

The command validates duplicate names and all menu references, then performs an exact transaction: missing permissions are inserted and database permissions absent from configuration are deleted. It synchronizes the code-owned matrices for all four fixed roles. Super Admin receives every non-portal permission plus only `portals.super-admin.access`; the other roles each receive only their own portal permission. The command refuses unsupported assigned roles, multi-role users, and non-bootstrap roleless users. A validation failure leaves the database unchanged.

## Menu catalog

`config/admin-menu.php` defines four independent manifests keyed by `super-admin`, `admin`, `instructor`, and `trainee`. Each manifest contains ordered items with these keys:

```php
    [
        'key' => 'users',
        'label' => 'Users',
        'icon' => 'users',
        'route' => 'admin.instructors.index',
        'permission' => 'users.manage-instructors',
        'order' => 10,
]
```

Top-level entries may contain `children`. Keys must be unique, routes must be named and registered, and every permission must exist in the permission catalog.

Run:

```bash
php artisan admin:menu-regenerate
```

The command validates every manifest and atomically replaces `bootstrap/cache/admin-menu.php`. The navigation service first selects the manifest for the user's exact fixed role, then filters it at request time with `$user->can(...)` and removes empty groups. It never merges menus from multiple roles. Route authorization remains mandatory even when an item is hidden.

The Admin manifest groups operational links under People, Course Overview, Test Overview, and System Settings. Admins have code-owned permissions for course-wide visibility and enrollment assignment, course and test reports, quiz creation and results, category/fiscal-year settings, and the read-only trainee Credit Score Viewer. The Super Admin manifest keeps its separate oversight groups; the Access Matrix route remains protected but is intentionally not exposed in the sidebar.

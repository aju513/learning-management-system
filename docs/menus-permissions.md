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

The command validates duplicate names and all menu references, then performs an exact transaction: missing permissions are inserted and database permissions absent from configuration are deleted. It synchronizes all configured permissions to the protected `super-admin` role. A validation failure leaves the database unchanged.

## Menu catalog

`config/admin-menu.php` defines ordered items with these keys:

```php
    [
        'key' => 'users',
        'label' => 'Users',
        'icon' => 'users',
        'route' => 'admin.users.index',
        'permission' => 'users.manage',
        'order' => 10,
]
```

Top-level entries may contain `children`. Keys must be unique, routes must be named and registered, and every permission must exist in the permission catalog.

Run:

```bash
php artisan admin:menu-regenerate
```

The command validates the definition and atomically replaces `bootstrap/cache/admin-menu.php`. The navigation service filters this compiled manifest at request time with `$user->can(...)` and removes empty groups. Route authorization remains mandatory even when an item is hidden.

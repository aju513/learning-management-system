# Feature Development

## Required sequence

1. Define the data model, migration, factory state, and repository contract.
2. Implement the Eloquent repository and bind it in `AppServiceProvider`.
3. Add FormRequests for filtered reads and every mutation.
4. Add a service for workflows, transactions, state changes, and activity events.
5. Add a thin controller and named `admin.*` routes.
6. Add stable permissions and server-side enforcement.
7. Add navigation only when the feature needs a sidebar destination.
8. Build views from the documented TailAdmin layouts/components.
9. Test authentication, authorization, validation, success, and important failure cases.
10. Update documentation in the same change.

## CRUD feature standard

Every new admin CRUD feature must use the following structure. The feature name below is a lowercase, stable key such as `products` or `invoices`.

```text
Route -> FormRequest -> Controller -> Service -> Repository contract -> Eloquent repository -> Model
                                      |             |
                                      |             +-- transactions, state changes, activity logging
                                      +----------------- response/view or redirect only
```

### Permissions

Declare all CRUD permissions in `config/permissions.php` before using them. Use the exact names `<feature>.<ability>`:

| Permission | Protects | UI behavior |
| --- | --- | --- |
| `<feature>.manage` | Index page, filters, pagination, and the sidebar entry | Shows the menu item and permits the management page |
| `<feature>.show` | Read-only detail page | Shows the View/Details action |
| `<feature>.create` | Create page and store action | Shows the Create button and permits submission |
| `<feature>.edit` | Edit page and update action | Shows the Edit button and permits submission |
| `<feature>.delete` | Delete action | Shows the Delete button and permits deletion |

Permissions are independent. `manage` does not silently grant `show`, `create`, `edit`, or `delete`; every route, FormRequest, and Blade action checks the specific ability it needs. The sidebar uses `manage`, and a parent menu group is hidden when the user has no visible children.

Domain-specific abilities may be added when CRUD alone is not enough (for example, `users.assign-roles` or `users.change-status`). They must be declared in the permission catalog and enforced independently at the route, request, service, and UI boundaries.

Run `php artisan admin:permissions-sync` after adding the catalog entries, then run `php artisan admin:menu-regenerate` when the feature has a sidebar destination.

### Routes and requests

Use named `admin.<feature>.*` routes and apply the matching `can:<feature>.<ability>` middleware to each route. Keep the authorization check in the FormRequest as a second server-side boundary:

```php
Route::middleware('can:products.manage')->get('products', ...)->name('admin.products.index');
Route::middleware('can:products.create')->get('products/create', ...)->name('admin.products.create');
Route::middleware('can:products.create')->post('products', ...)->name('admin.products.store');
Route::middleware('can:products.edit')->get('products/{product}/edit', ...)->name('admin.products.edit');
Route::middleware('can:products.edit')->put('products/{product}', ...)->name('admin.products.update');
Route::middleware('can:products.delete')->delete('products/{product}', ...)->name('admin.products.destroy');
Route::middleware('can:products.show')->get('products/{product}', ...)->name('admin.products.show');
```

Register static paths such as `create` before `{product}` so route model binding cannot interpret `create` as an identifier.

Use a dedicated FormRequest for each input boundary: an index/filter request, store request, update request, and delete request. A show request is required when the detail page has route-specific authorization or input. Requests own strict validation, normalization, and authorization; controllers must not duplicate these rules.

### Controller, service, and repository responsibilities

- The controller is thin. It receives an authorized request and route-bound model, calls one service method, and returns a view, redirect, or validation response. It contains no queries, transactions, or business rules.
- Response handling stays at the HTTP boundary: render named Blade views for reads, redirect to a named route with a safe flash message after mutations, and let FormRequest validation return the standard validation response. Do not return repository models directly from a service.
- The service owns the CRUD workflow. Create, update, and delete operations use transactions when more than one write is involved, enforce invariants, synchronize relationships, and record the activity event with the authenticated actor.
- The repository contract defines reusable persistence operations such as `paginateForIndex`, `findOrFail`, `create`, `update`, and `delete`. The Eloquent repository owns filtering, sorting, eager loading, and pagination only; business decisions stay in the service.
- Bind the contract to its Eloquent implementation in `AppServiceProvider` and inject the contract into the service.
- Models define relationships, casts, and small state helpers. They do not replace the service or repository layers.

### Standard views

The normal CRUD surface has four Blade files under `resources/views/pages/admin/<feature>/`:

1. `index.blade.php` — page breadcrumb, authorized Create action, GET filter form above the table, paginated results, empty state, and per-row View/Edit/Delete actions gated by their individual permissions.
2. `create.blade.php` — validated create form posting to the named store route.
3. `edit.blade.php` — validated edit form posting to the named update route.
4. `show.blade.php` — read-only detail view with an authorized Edit action when applicable.

An optional `_form.blade.php` partial may be shared by create and edit. Keep filtering in the repository and preserve query parameters in pagination links. Use TailAdmin layout/components, render validation errors, and never mutate state in Blade. Build form fields from the reusable components documented in `docs/ui-components.md` (`x-form.input`, `x-form.select`, `x-form.searchable-select`, `x-form.textarea`, `x-form.date-picker`, `x-form.multiselect`, `x-form.toggle`, `x-form.checkbox`, `x-form.file-upload`, and `x-form.editor`). Add a reusable component before introducing repeated field markup. File-upload forms must use `multipart/form-data`, and upload rules belong in the FormRequest.

### CRUD table layout

Keep every CRUD index table consistent with the users table pattern:

- Start with the page breadcrumb and an authorized create action.
- Place the GET filter form directly above the table inside the component card.
- Wrap the table in `overflow-x-auto` and use `min-w-full divide-y divide-gray-200 dark:divide-gray-800`.
- Use uppercase, muted table headers and `divide-y divide-gray-100 dark:divide-gray-800` for the body.
- Use compact horizontal padding (`px-2`) for serial-number, status, and selection columns; use `px-4` for remaining data and action columns.
- Use `py-4` for body cells, keep row actions right-aligned, gate each action with its specific permission, and include a paginated empty state.

When a resource does not have status or selection controls, keep the same structure and apply compact spacing only to the serial-number column.

### Activity tracking

Every successful create, update, delete, status change, relationship synchronization, or other state-changing CRUD workflow must write an activity event from the service. Include the event name, actor, subject, and safe identifiers/changed fields. Never record passwords, tokens, credentials, session values, or full request payloads. Failed validation and denied authorization should not create a misleading success event.

### CRUD test checklist

Add Pest feature coverage for each feature:

- authorized index, filters, pagination, show, create, edit, update, and delete behavior;
- unauthenticated redirects and per-permission authorization for every route and action;
- validation failures for required, unique, format, relationship, and destructive-operation rules;
- successful activity records containing the authenticated actor and safe subject data;
- important failure paths, including missing records, invariant violations, and transaction rollback.

## Review rules

Reject changes that query models in controllers, mutate state in Blade, put business rules in repositories, rely only on hidden menu items for security, introduce unconfigured permissions, log secrets, or add undocumented architectural exceptions.

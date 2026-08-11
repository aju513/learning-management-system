# TailAdmin UI Components

## Layouts

- `layouts.app` is the authenticated shell: responsive sidebar, header, flash/error feedback, dark mode, and content container.
- `layouts.fullscreen-layout` is for login and password-reset screens.
- Page views set a title, extend a layout, and render content through `@section('content')`.

## Reusable components

- `<x-common.page-breadcrumb>` supplies the page heading.
- `<x-common.component-card>` supplies a consistent bordered card with title, description, and slot.
- `<x-ui.button>` supports `primary`, `secondary`, `success`, `danger`, `warning`, `info`, and `outline` variants plus `sm`/`md` sizes. Buttons include focus, hover, and disabled states.
- `<x-ui.badge>` supports status colors and light/solid variants.
- `<x-ui.alert>` supports success, warning, error, and information feedback.
- `<x-common.menu-icon>` renders the named SVG icon used by the generated sidebar menu.

The protected `/admin/ui-kit` page is the executable catalog. New shared components must include sensible defaults, dark-mode styles, accessible labels/focus states, an example there, and a documentation update here.

Forms currently share the TailAdmin input class pattern used by the user/role/profile screens. Always render server validation errors through the authenticated layout or auth form feedback.

Page headers use `<x-common.page-breadcrumb>` for a consistently left-aligned title and breadcrumb. Pages with a header action should pass it through the component's `actions` slot so the action remains aligned on the right at desktop widths and stacks cleanly on small screens.
